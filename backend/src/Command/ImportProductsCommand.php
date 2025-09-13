<?php declare(strict_types=1);

namespace App\Command;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use SplFileObject;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import:products',
    description: 'Import products from CSV (identifier;name;category_id;category;price;net_price;image_url[;description])'
)]
final class ImportProductsCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'file',
            InputArgument::OPTIONAL,
            'CSV file path',
            __DIR__ . '/../../sample-data/products.csv'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $path = (string) $input->getArgument('file');

        if (!is_file($path)) {
            $io->error(sprintf('CSV not found: %s', $path));
            return Command::FAILURE;
        }

        $csv = new SplFileObject($path);
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $csv->setCsvControl(';'); // nálad pontosvessző a szeparátor

        /** @var string[] $header */
        $header = [];

        $imported = 0;
        $updated  = 0;
        $skipped  = 0;

        $batchSize = 200;
        $rows      = 0;

        $productRepo  = $this->em->getRepository(Product::class);
        $categoryRepo = $this->em->getRepository(Category::class);

        foreach ($csv as $i => $row) {
            if ($row === false || $row === [null]) {
                continue;
            }

            // BOM eltávolítás az első cellából
            if ($i === 0 && isset($row[0])) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);
            }

            // Fejléc sor
            if ($i === 0) {
                $header = array_map(
                    static fn($v) => strtolower(trim((string) $v)),
                    $row,
                );
                continue;
            }

            // Üres sor eldobása
            if (!is_array($row) || count(array_filter($row, static fn($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }

            if ($header === []) {
                $io->error('CSV header is missing or empty.');
                return Command::FAILURE;
            }

            $values = array_map(
                static fn($v) => $v === null ? null : trim((string) $v),
                $row,
            );

            /** @var array<string, string|null> $data */
            $data = array_combine($header, $values) ?: [];

            $identifier = (string) ($data['identifier'] ?? '');
            if ($identifier === '') {
                $skipped++;
                continue;
            }

            $name = (string) ($data['name'] ?? '');
            $img  = (string) ($data['image_url'] ?? '');
            $slug = $this->slugify($name);

            // description: oszlop hiányában default szöveg
            $desc = '';
            if (array_key_exists('description', $data)) {
                /** @var ?string $d */
                $d    = $data['description'];
                $desc = (string) ($d ?? '');
            }
            if ($desc === '') {
                $desc = 'Lorem ipsum dolor sit amet.';
            }

            $price    = (float) str_replace(',', '.', (string) ($data['price'] ?? '0'));
            $netPrice = (float) str_replace(',', '.', (string) ($data['net_price'] ?? '0'));
            $priceCents    = (int) round($price * 100);
            $netPriceCents = (int) round($netPrice * 100);

            // Kategória feloldás: először ID, aztán név, hiány esetén létrehozás
            $category = null;
            if (!empty($data['category_id'])) {
                $category = $categoryRepo->find((int) $data['category_id']);
            }
            if (!$category) {
                $catName = (string) ($data['category'] ?? 'Egyéb');
                $category = $categoryRepo->findOneBy(['name' => $catName]);
                if (!$category) {
                    $category = new Category();
                    $category->setName($catName);
                    $category->setSlug($this->slugify($catName));
                    $category->setDescription(null);
                    $now = new \DateTimeImmutable();
                    $category->setCreatedAt($now);
                    $category->setUpdatedAt($now);
                    $this->em->persist($category);
                }
            }

            $now = new \DateTimeImmutable();

            /** @var ?Product $product */
            $product = $productRepo->findOneBy(['identifier' => $identifier]);

            if ($product) {
                // Update meglévő rekord
                $product->setName($name);
                $product->setSlug($slug);
                $product->setDescription($desc);
                $product->setImageUrl($img);
                $product->setPriceCents($priceCents);
                $product->setNetPriceCents($netPriceCents);
                $product->setCategory($category);
                $product->setUpdatedAt($now);
                $updated++;
            } else {
                // Insert új rekord
                $product = new Product();
                $product->setIdentifier($identifier);
                $product->setName($name);
                $product->setSlug($slug);
                $product->setDescription($desc);
                $product->setImageUrl($img);
                $product->setPriceCents($priceCents);
                $product->setNetPriceCents($netPriceCents);
                $product->setCategory($category);
                $product->setCreatedAt($now);
                $product->setUpdatedAt($now);

                $this->em->persist($product);
                $imported++;
            }

            $rows++;

            if (($rows % $batchSize) === 0) {
                $this->em->flush();
                $this->em->clear();
                $productRepo  = $this->em->getRepository(Product::class);
                $categoryRepo = $this->em->getRepository(Category::class);
            }
        }

        // Végső flush a maradékra
        $this->em->flush();

        $io->success(sprintf('Imported: %d, Updated: %d, Skipped: %d', $imported, $updated, $skipped));
        return Command::SUCCESS;
    }

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($t !== false) {
            $text = $t;
        }
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text) ?? '';
        return trim((string) $text, '-') ?: 'n-a';
    }
}
