# Architectural Rules & Standards

## Pattern: Pure MVC (Model-View-Controller)
- **Models:** Contain all database interactions (MySQL via PDO/prepared statements). No HTML or direct output (`echo`/`print`) permitted in models.
- **Views:** Contain presentation logic, Tailwind CSS classes, and template rendering. Views MUST NOT run SQL queries or connect to the database.
- **Controllers:** Handle requests, validate input, fetch data from Models, and render/include Views. No raw SQL allowed in controllers.
- **Routing/Entry:** Central entry point dispatching requests to controllers.

## Configuration & Security
- All sensitive credentials (DB_HOST, DB_USER, DB_PASS, DB_NAME) MUST be stored in `.env`.
- Use a lightweight `.env` parser or `vlucas/phpdotenv`.
- NEVER commit `.env` to Git; maintain an up-to-date `.env.example`.
- All SQL queries must use prepared statements (prevent SQL injection).

## Frontend Guidelines
- Use Tailwind CSS utility classes within View templates.
- Keep reusable components (headers, footers, navigation, modals) isolated in `/views/components/` or `/views/layouts/`.
