# Access Control

Access rules are defined in:

- `config/common/access.php`

Each key is a permission name (example `brand.view`) mapped to a callable that receives an `Actor` and returns boolean.

Integration point for enforcing these rules should be applied in actions or a dedicated middleware/guard.
