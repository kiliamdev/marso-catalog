<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;


#[AsController]
final class RandomProductsAction
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    /**
     * Egyedi művelet, ami random termékeket ad vissza.
     *
     * @return Product[]
     */
    public function __invoke(Request $request): array
    {
        $count = max(1, min(12, (int) $request->query->get('count', 4)));

        return $this->em->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->addSelect('RAND() as HIDDEN r')
            ->orderBy('r', 'ASC')
            ->setMaxResults($count)
            ->getQuery()
            ->getResult();
    }
}
