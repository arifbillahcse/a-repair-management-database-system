<?php
/**
 * ProductCategory — simple category list for products.
 * Table is created by Product::ensureSchema().
 */
class ProductCategory extends BaseModel
{
    protected string $table      = 'product_categories';
    protected string $primaryKey = 'category_id';

    /** All categories ordered for display, with product counts. */
    public function allWithCounts(): array
    {
        return $this->db->fetchAll(
            "SELECT pc.*, COUNT(p.product_id) AS product_count
             FROM product_categories pc
             LEFT JOIN products p ON p.category_id = pc.category_id AND p.is_active = 1
             GROUP BY pc.category_id
             ORDER BY pc.sort_order ASC, pc.name ASC"
        );
    }

    /** Plain ordered list for select dropdowns. */
    public function allOrdered(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM product_categories ORDER BY sort_order ASC, name ASC"
        );
    }

    public function isNameTaken(string $name, ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM product_categories WHERE name = ?";
        $params = [$name];
        if ($excludeId) {
            $sql     .= " AND category_id != ?";
            $params[] = $excludeId;
        }
        return (int)$this->db->fetchScalar($sql, $params) > 0;
    }

    /** Delete a category and unlink its products (bare category_id column). */
    public function deleteAndUnlink(int $id): void
    {
        $this->db->update('products', ['category_id' => null], 'category_id = ?', [$id]);
        $this->delete($id);
    }
}
