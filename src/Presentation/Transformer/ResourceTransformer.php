<?php

namespace App\Presentation\Transformer;

use App\Domain\Model\Entity\Client;
use App\Domain\Model\Entity\Invoice;
use App\Domain\Model\Entity\Order;
use App\Domain\Model\Entity\OrderLine;
use App\Domain\Model\Entity\Product;
use DateTimeInterface;

final class ResourceTransformer
{
    public static function product(Product $product): array
    {
        return [
            'id'             => $product->getId(),
            'name'           => $product->getName(),
            'reference'      => $product->getReference(),
            'description'    => $product->getDescription(),
            'price'          => $product->getPrice()->toArray(),
            'stock'          => [
                'quantity'       => $product->getStockQuantity(),
                'minimum'        => $product->getMinimumStock(),
                'available'      => $product->isAvailable(),
                'below_minimum'  => $product->isBelowMinimumStock(),
                'out_of_stock'   => $product->isOutOfStock(),
            ],
            'is_active'      => $product->isActive(),
            'created_at'     => $product->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updated_at'     => $product->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }

    public static function client(Client $client): array
    {
        return [
            'id'          => $client->getId(),
            'last_name'   => $client->getLastName(),
            'first_name'  => $client->getFirstName(),
            'full_name'   => $client->getFullName(),
            'email'       => $client->getEmail(),
            'phone'       => $client->getPhone(),
            'address'     => $client->getAddress(),
            'is_active'   => $client->isActive(),
            'created_at'  => $client->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updated_at'  => $client->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }

    public static function order(Order $order): array
    {
        return [
            'id'            => $order->getId(),
            'number'        => $order->getNumber(),
            'client_id'     => $order->getClientId(),
            'status'        => [
                'code'  => $order->getStatus()->value,
                'label' => $order->getStatus()->label(),
            ],
            'order_lines'   => array_map(
                fn(OrderLine $line) => self::orderLine($line),
                $order->getOrderLines()
            ),
            'total_amount'  => $order->getTotalAmount()->toArray(),
            'customer_note' => $order->getCustomerNote(),
            'created_at'    => $order->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updated_at'    => $order->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }

    public static function orderLine(OrderLine $line): array
    {
        return [
            'id'                => $line->getId(),
            'product_id'        => $line->getProductId(),
            'product_name'      => $line->getProductName(),
            'reference'         => $line->getProductReference(),
            'quantity'          => $line->getQuantity(),
            'unit_price'        => $line->getUnitPrice()->toArray(),
            'sub_total'         => $line->getSubTotal()->toArray(),
        ];
    }

    public static function invoice(Invoice $invoice): array
    {
        return [
            'id'             => $invoice->getId(),
            'number'         => $invoice->getNumber(),
            'order_id'       => $invoice->getOrderId(),
            'client_id'      => $invoice->getClientId(),
            'status'         => [
                'code'  => $invoice->getStatus()->value,
                'label' => $invoice->getStatus()->label(),
            ],
            'amounts'        => [
                'excl_tax'  => $invoice->getAmountExclTax()->toArray(),
                'tax_rate'  => $invoice->getTaxRate(),
                'tax'       => $invoice->getTaxAmount()->toArray(),
                'incl_tax'  => $invoice->getAmountInclTax()->toArray(),
            ],
            'dates'          => [
                'due_date'   => $invoice->getDueDate()->format(DateTimeInterface::ATOM),
                'paid_at'    => $invoice->getPaidAt()?->format(DateTimeInterface::ATOM),
                'is_overdue' => $invoice->isOverdue(),
            ],
            'created_at'     => $invoice->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updated_at'     => $invoice->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
