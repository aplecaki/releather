# ReLeather

A small PHP site for a fictional brand that makes couches from reused leather.
Started as part of the class Git practice repository `rwarners/gitoefening`.

## Pages

- `index.php` – landing page (static, no database needed)
- `dashboard.php` – stats, couch categories and live inventory, read from MySQL
- `couches.php` – add, edit, delete and mark couches as sold
- `nav.php` – shared menu used on every page
- `connection.php` – database connection
- `style.css` – shared styles for all pages
- `schema.sql` – database tables and sample data

## Setup

1. Put the folder where your PHP server can serve it (XAMPP `htdocs`, Herd, ...).
2. Start MySQL and import the database (this recreates the `couches` and
   `categories` tables in the `fullstack` database):

   ```
   mysql -u root < schema.sql
   ```

3. Open `index.php` in the browser.

The connection defaults to `localhost`, user `root`, no password, database
`fullstack`. Override with the environment variables `DB_HOST`, `DB_USER`,
`DB_PASS` and `DB_NAME`.
