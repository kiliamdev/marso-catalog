<?php declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Product;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class RandomProductsProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $req = $this->requestStack->getCurrentRequest();
        $count = $req ? (int) $req->query->get('count', 4) : 4;
        $count = max(1, min(12, $count));

        $conn = $this->em->getConnection();
        $platform = $conn->getDatabasePlatform()->getName(); // 'mysql', 'sqlite', 'postgresql', ...

        // RAND() MySQL-en, RANDOM() SQLite-on/PostgreSQL-en
        $randFunc = match ($platform) {
            'sqlite' => 'RANDOM()',
            'postgresql' => 'RANDOM()',
            default => 'RAND()', // mysql/mariadb
        };

        // 1) kérjünk ki véletlen ID-kat natív SQL-lel
        $sql = "SELECT id FROM product ORDER BY $randFunc LIMIT :limit";
        $ids = $conn->executeQuery($sql, ['limit' => $count], ['limit' => \PDO::PARAM_INT])
            ->fetchFirstColumn();

        if (!$ids) {
            return [];
        }

        // 2) töltsük vissza az entitásokat; a findBy nem garantálja a sorrendet, ezért kézzel rendezzük
        /** @var Product[] $items */
        $items = $this->em->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->getResult();

        // indexeljük id alapján, hogy az eredeti random sorrendet tartsuk
        $byId = [];
        foreach ($items as $p) {
            $byId[$p->getId()] = $p;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }
}
