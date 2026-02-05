# Contributing to UC Laravel

Thank you for your interest in contributing to UC Laravel! This document provides guidelines for contributing to the project.

## Getting Started

1. **Fork the repository** and clone it locally
2. **Install dependencies**: `composer install && pnpm install`
3. **Set up your environment**: Copy `.env.example` to `.env` and configure
4. **Run migrations**: `php artisan migrate`
5. **Start development**: `composer dev` (runs Laravel + Vite)

## Development Workflow

### Making Changes

1. **Create a feature branch** from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes** following the project patterns (see `.github/copilot-instructions.md`)

3. **Write tests**:
   - Extend `DatabaseTestCase` for tests needing database access
   - Extend `TestCase` for unit tests without database
   - Run tests: `composer test` (PHP) and `pnpm test` (TypeScript)

4. **Lint and format**:
   ```bash
   # PHP
   ./vendor/bin/phpstan analyse
   
   # TypeScript (if configured)
   pnpm lint
   ```

5. **Commit your changes** with clear, descriptive messages:
   ```bash
   git commit -m "feat: add feature description"
   ```

### Commit Message Format

Follow [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation changes
- `style:` Code style changes (formatting, etc.)
- `refactor:` Code refactoring
- `test:` Adding or updating tests
- `chore:` Maintenance tasks

Example: `feat: add manifest allocation diversity check`

### Pull Request Process

1. **Push your branch** to your fork
2. **Open a Pull Request** against the `main` branch
3. **Fill in the PR template** with details about your changes
4. **Ensure CI passes** (tests, linting)
5. **Address review feedback** if requested
6. **Squash commits** if requested before merge

## Code Guidelines

### PHP

- Follow PSR-12 coding standards
- Use type hints for parameters and return types
- Write DocBlocks for complex methods
- Keep controllers thin, use service classes for business logic
- Follow the shop-scoped pattern for multi-tenant features

### TypeScript/React

- Use functional components with hooks
- Follow existing patterns in `resources/js/`
- Use shadcn/ui components (not react-bootstrap)
- Use `fetchWrapper` for API calls with CSRF tokens
- Use `<Skeleton>` components for loading states

### Testing

- **CRITICAL**: PHP tests MUST use SQLite (enforced in `phpunit.xml`)
- Write tests for new features and bug fixes
- Aim for meaningful coverage, not just high percentages
- Use helper methods in `DatabaseTestCase`: `createTestUser()`, `createTestShop()`, etc.

### Database

- Create MySQL migrations as usual
- **IMPORTANT**: Update `database/schema/sqlite-schema.sql` with SQLite-compatible schema
- Run tests to verify SQLite schema works

## Project Structure

Key directories and files:

```
app/
├── Http/Controllers/     # API and web controllers
├── Models/              # Eloquent models
├── Services/            # Business logic services
└── Http/Middleware/     # Custom middleware

resources/js/            # React/TypeScript frontend
├── components/ui/       # shadcn/ui components
└── *.tsx               # Page entrypoints

routes/
├── api.php             # API routes
└── web.php             # Web routes (Blade shell templates)

tests/
├── Feature/            # Feature/integration tests
└── Unit/              # Unit tests
```

See `.github/copilot-instructions.md` for detailed architecture documentation.

## Multi-Tenant Considerations

When working on features that touch multiple stores:

1. **Shop-scoped routes**: Use `Route::prefix('shops/{shop}')` pattern
2. **Access control**: Apply appropriate middleware (`shop.access:read` or `shop.access:write`)
3. **Services**: Create shop-specific service instances with `ShopifyClient($shop)`
4. **Testing**: Use `grantShopAccess()` helper to set up user permissions

## Shopify Integration

When modifying Shopify integration:

1. **Use service classes**: `ShopifyProductService`, `ShopifyOrderService`, etc.
2. **Caching**: Respect the 1-hour cache TTL for product data
3. **GraphQL**: Add new queries to appropriate service classes
4. **Webhooks**: Test webhook handling with HMAC verification

## Getting Help

- Check `.github/copilot-instructions.md` for architecture details
- Review existing code for patterns and examples
- Ask questions in pull request discussions
- Refer to README.md for basic setup and usage

## Code of Conduct

- Be respectful and inclusive
- Provide constructive feedback
- Focus on the code, not the person
- Help others learn and grow

## License

By contributing, you agree that your contributions will be licensed under the same license as the project.

Thank you for contributing! 🎉
