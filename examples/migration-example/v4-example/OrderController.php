<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pgs\HashIdBundle\Attribute\Hash;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/order')]
class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'order_index', methods: ['GET'])]
    public function index(): Response
    {
        $orders = $this->orderRepository->findAll();

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/{id}', name: 'order_show', methods: ['GET'])]
    #[Hash('id')]
    public function show(int $id): Response
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'order_edit', methods: ['GET', 'POST'])]
    #[Hash('id')]
    public function edit(Request $request, int $id): Response
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        if ($request->isMethod('POST')) {
            $order->setCustomerName($request->request->get('customer_name'));
            $order->setTotalAmount($request->request->get('total_amount'));
            $order->setStatus($request->request->get('status'));

            $this->entityManager->flush();

            $this->addFlash('success', 'Order updated successfully!');

            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/delete', name: 'order_delete', methods: ['POST'])]
    #[Hash('id')]
    public function delete(Request $request, int $id): Response
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($order);
            $this->entityManager->flush();
            $this->addFlash('success', 'Order deleted successfully!');
        }

        return $this->redirectToRoute('order_index');
    }

    #[Route('/new', name: 'order_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $order = new Order();
            $order->setOrderNumber('ORD-' . uniqid());
            $order->setCustomerName($request->request->get('customer_name'));
            $order->setTotalAmount($request->request->get('total_amount'));
            $order->setStatus('pending');

            $this->entityManager->persist($order);
            $this->entityManager->flush();

            $this->addFlash('success', 'Order created successfully!');

            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        return $this->render('order/new.html.twig');
    }

    #[Route('/{orderId}/item/{itemId}', name: 'order_item', methods: ['GET'])]
    #[Hash(['orderId', 'itemId'])]
    public function orderItem(int $orderId, int $itemId): Response
    {
        // Example with multiple hashed parameters
        $order = $this->orderRepository->find($orderId);

        return $this->render('order/item.html.twig', [
            'order' => $order,
            'itemId' => $itemId,
        ]);
    }
}