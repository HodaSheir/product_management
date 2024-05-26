<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use SimpleXMLElement;
use App\Entity\Product;
use Symfony\Component\HttpKernel\KernelInterface;

class ImportController extends AbstractController
{
    #[Route('/import', name: 'app_import')]
    public function import(Request $request, EntityManagerInterface $em, KernelInterface $kernel): Response
    {
        if ($request->isMethod('POST')) {
            $file = $request->files->get('import_file');
            if (!$file) {
                return new Response('No file uploaded', Response::HTTP_BAD_REQUEST);
            }

            $filePath = $file->getRealPath();
            $batchSize = 100; // Adjust based on performance testing
            $i = 0;
            $importResults = [];

            $reader = new \XMLReader();
            $reader->open($filePath);

            while ($reader->read() && $reader->name !== 'product') {
                // Move to the first product node
            }

            while ($reader->name === 'product') {
                $xmlProduct = simplexml_load_string($reader->readOuterXML());
                $product = new Product();
                $product->setTitle((string) $xmlProduct->title);
                $product->setDescription((string) $xmlProduct->description);
                $product->setWeight((float) $xmlProduct->weight);
                $product->setCategory((string) $xmlProduct->category);

                $em->persist($product);

                $importResults[] = [
                    'id' => $product->getId(),
                    'title' => (string) $xmlProduct->title,
                    'status' => 'success'
                ];

                if (($i % $batchSize) === 0) {
                    $em->flush();
                    $em->clear(); // Detaches all objects from Doctrine
                }

                $i++;
                $reader->next('product');
            }

            $em->flush(); // Flush the remaining objects
            $em->clear(); // Clear the EntityManager
            $reader->close();

            // Generate the CSV report
            $csvFilePath = $this->generateCsvReport($importResults, $kernel->getProjectDir());

            return new Response('Import successful! Report generated: ' . $csvFilePath, Response::HTTP_OK);
        }

        return $this->render('import/index.html.twig');
    }

    private function generateCsvReport(array$importResults, string $projectDir): string
    {
        $publicDir = $projectDir . '/public';
        $csvFilePath = $publicDir . '/report.csv';

        // Ensure the directory exists
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }

        $handle = fopen($csvFilePath, 'w+');

        // Add the header
        fputcsv($handle, ['ID', 'Title', 'Status']);

        // Add the rows
        foreach ($importResults as $result) {
            fputcsv($handle, $result);
        }

        fclose($handle);

        return $csvFilePath;
    }
}
