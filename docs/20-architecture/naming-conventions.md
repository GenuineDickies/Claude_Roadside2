# Naming Conventions

## Database
- Table names: `snake_case` (e.g., `service_requests`, `work_orders`)
- Column names: `snake_case` (e.g., `customer_id`, `created_at`)
- Primary keys: `id` (auto-increment INT)
- Foreign keys: `{table_singular}_id` (e.g., `customer_id`)
- Timestamps: `created_at`, `updated_at`

## PHP
- Variables: `$camelCase`
- Functions: `snake_case()` (following WordPress/PHP convention)
- Classes: Not used (procedural style)
- File names: `kebab-case.php` (e.g., `service-intake.php`)

## JavaScript
- Variables: `camelCase`
- Functions: `camelCase()`
- Objects/Namespaces: `PascalCase` (e.g., `Director`, `ServiceCatalog`)
- Event handlers: `on{Event}` or descriptive name

## CSS
- Page scoping prefix: `.{page}-*` (e.g., `.intake-*`, `.est-*`, `.director-*`)
- CSS variables: `--{category}-{name}` (e.g., `--bg-primary`, `--navy-500`)
- BEM-like: `.component-element` (e.g., `.director-stat-value`)

## File Structure
- Pages: `pages/{feature}.php`
- APIs: `api/{feature}.php`
- Docs: `docs/{NN}-{phase}/{artifact}.md`

## Last Updated
2026-02-12