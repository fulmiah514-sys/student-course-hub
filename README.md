# Student Course Hub — CTEC2712 Web Project

## Setup

1. Create the database: `mysql -u root -p < example_data.sql`
2. Apply the schema updates: `mysql -u root -p < schema_updates.sql`
3. Edit `includes/db.php` with your MySQL username/password.
4. Serve the folder with PHP's built-in server for local testing:
   `php -S localhost:8000` (run from the project root)
5. Visit `http://localhost:8000/` for the student site,
   `http://localhost:8000/admin/login.php` for the admin area.
6. Default admin login: **admin / ChangeMe123!**
   Change it by running `php includes/seed_admin.php "YourNewPassword"`
   and updating the `PasswordHash` value in the `Admins` table with the output.

## What was built, mapped to the brief

**Student-facing site** (`index.php`, `programme.php`)
- Browse/search/filter published programmes by keyword and level
- Programme detail page: modules grouped by year, staff (leader) info,
  a "shared across programmes" tag on modules taught in multiple programmes
- Register-interest form submitted via AJAX (`interest.php`, `js/interest.js`)
  with no page reload
- Responsive layout + visible keyboard focus states for accessibility (WCAG)
- Alt text fields for programme/module images

**Admin interface** (`admin/`)
- Login with hashed passwords (`password_hash`/`password_verify`), sessions,
  CSRF tokens on every form
- Role-based access control (`AdminRoles` table: SuperAdmin / ProgrammeEditor
  / RecruitmentViewer) enforced via `includes/auth.php`
- Create/edit/delete programmes, publish/unpublish toggle (draft workflow)
- Student interest ("mailing list") view: filter by programme, remove
  invalid/duplicate entries, CSV export, unsubscribe (`IsActive`) tracking

**Security**
- All queries use PDO prepared statements (SQL injection prevention)
- All dynamic output passed through `h()` (`htmlspecialchars`) before
  being echoed into HTML (XSS prevention)
- CSRF tokens verified on every state-changing POST request
- Passwords hashed with bcrypt via `password_hash`

**Database changes** (`schema_updates.sql`) — implements every "possible
change" suggested in the brief: `IsPublished` flag, richer `Staff` profile
fields, `UNIQUE(ProgrammeID, Email)` to stop duplicate sign-ups, `IsActive`
for unsubscribes, `ImageAltText` columns, `Admins`/`AdminRoles` tables, and
extra indexes for search performance.

## Suggested individual contributions to film for the video

Pick 1–2 of these (they map cleanly onto the "core feature / refinement /
security" categories in the brief):

1. **Core feature**: publish/unpublish workflow — `IsPublished` column +
   toggle in `admin/dashboard.php` + filtering it out in `index.php`.
2. **Security enhancement**: admin authentication — hashed passwords,
   session handling, CSRF protection (`admin/login.php`, `includes/auth.php`).
3. **Refinement/bug fix**: duplicate sign-up prevention — the
   `UNIQUE(ProgrammeID, Email)` constraint plus the `ON DUPLICATE KEY UPDATE`
   handling in `interest.php`.
4. **Accessibility refinement**: alt text fields + visible focus states in
   `css/style.css`.

For the video: commit each piece separately in your own GitHub repo so the
commit history actually shows the work, then follow the 4-part structure
(intro → technical explanation with commit diffs → live demo → impact).
