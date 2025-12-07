<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Dompdf\Dompdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class InvoiceController extends AbstractController
{
    #[Route('/compte/facture/impression/{id_order}', name: 'app_invoice_customer')]
    public function printForCustomer(OrderRepository $orderRepository, $id_order): Response
    {
        // 1. vérification de l'objet commande - Existe ?
        $order = $orderRepository->findOneById($id_order);

        if (!$order) {
            return $this->redirectToRoute('app_account');
        }

        // 2. vérification de l'objet commande - Ok pour l'utilisateur?
        if ($order->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('app_account');
        }
        // Create DomPDF instance
        $dompdf = new Dompdf();
        // Render Twig template to HTML string
        $html = $this->renderView('invoice/index.html.twig',[
            'order' => $order
        ]);
        // Load HTML into DomPDF
        $dompdf->loadHtml($html);
        // Set paper size
        $dompdf->setPaper('A4', 'portrait');
        // Generate the PDF
        $dompdf->render();
        // Output to browser (Attachment => false means display , not download)
        $dompdf->stream('facture.pdf', [
            'Attachment' => false
        ]);

        exit();

    }
    #[Route('/admin/facture/impression/{id_order}', name: 'app_invoice_admin')]
    public function printForAdmin(OrderRepository $orderRepository, $id_order): Response
    {
        // 1. vérification de l'objet commande - Existe ?
        $order = $orderRepository->findOneById($id_order);

        if (!$order) {
            return $this->redirectToRoute('admin');
        }

        $dompdf = new Dompdf();
        $html = $this->renderView('invoice/index.html.twig',[
            'order' => $order
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('facture.pdf', [
            'Attachment' => false
        ]);

        exit();

    }
}
