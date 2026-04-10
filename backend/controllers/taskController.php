<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Product.php';

class TaskController
{
    private PDO $conn;
    private Product $productModel;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
        $this->productModel = new Product($db);
    }

    public function getProducts(): array
    {
        return [
            'success' => true,
            'data' => $this->productModel->getAll(),
        ];
    }

    public function createProduct(array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $price = (float) ($payload['price'] ?? 0);
        $category = (string) ($payload['category'] ?? '');
        $stock = (int) ($payload['stock'] ?? 0);
        $imageUrl = isset($payload['image_url']) ? trim((string) $payload['image_url']) : null;

        if ($name === '' || $price <= 0 || $category === '') {
            return [
                'success' => false,
                'message' => 'Invalid product data.',
            ];
        }

        $id = $this->productModel->create($name, $price, $category, max(0, $stock), $imageUrl ?: null);

        return [
            'success' => true,
            'message' => 'Product created successfully.',
            'product_id' => $id,
        ];
    }

    public function createSale(array $payload): array
    {
        $productId = (int) ($payload['product_id'] ?? 0);
        $quantity = (int) ($payload['quantity'] ?? 0);

        if ($productId <= 0 || $quantity <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid sale data.',
            ];
        }

        $product = $this->productModel->findById($productId);
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found.',
            ];
        }

        if ((int) $product['stock'] < $quantity) {
            return [
                'success' => false,
                'message' => 'Insufficient stock.',
            ];
        }

        $totalPrice = (float) $product['price'] * $quantity;

        try {
            $this->conn->beginTransaction();

            $updated = $this->productModel->reduceStock($productId, $quantity);
            if (!$updated) {
                $this->conn->rollBack();
                return [
                    'success' => false,
                    'message' => 'Failed to update stock.',
                ];
            }

            $stmt = $this->conn->prepare(
                'INSERT INTO sales (product_id, quantity, total_price) VALUES (:product_id, :quantity, :total_price)'
            );
            $stmt->execute([
                'product_id' => $productId,
                'quantity' => $quantity,
                'total_price' => number_format($totalPrice, 2, '.', ''),
            ]);

            $saleId = (int) $this->conn->lastInsertId();
            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Sale recorded successfully.',
                'sale_id' => $saleId,
                'total_price' => round($totalPrice, 2),
            ];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return [
                'success' => false,
                'message' => 'Failed to create sale.',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function updateProduct(array $payload): array
    {
        $id = (int) ($payload['id'] ?? 0);
        $name = trim((string) ($payload['name'] ?? ''));
        $price = (float) ($payload['price'] ?? 0);
        $category = (string) ($payload['category'] ?? '');
        $stock = (int) ($payload['stock'] ?? 0);
        $imageUrl = isset($payload['image_url']) ? trim((string) $payload['image_url']) : null;

        if ($id <= 0 || $name === '' || $price <= 0 || $category === '') {
            return [
                'success' => false,
                'message' => 'Invalid product data for update.',
            ];
        }

        $existing = $this->productModel->findById($id);
        if (!$existing) {
            return [
                'success' => false,
                'message' => 'Product not found.',
            ];
        }

        $updated = $this->productModel->update($id, $name, $price, $category, max(0, $stock), $imageUrl ?: null);

        return [
            'success' => $updated,
            'message' => $updated ? 'Product updated successfully.' : 'No changes applied.',
        ];
    }

    public function getSales(): array
    {
        $stmt = $this->conn->query(
            'SELECT s.id, s.product_id, p.name AS product_name, s.quantity, s.total_price, s.sale_date
             FROM sales s
             LEFT JOIN products p ON p.id = s.product_id
             ORDER BY s.sale_date DESC, s.id DESC'
        );

        return [
            'success' => true,
            'data' => $stmt->fetchAll(),
        ];
    }
}


