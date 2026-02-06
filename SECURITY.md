# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability in UC Laravel, please report it responsibly. **Do not open a public issue.**

### How to Report

1. **Email**: Send details to the repository maintainer(s)
2. **Include**: 
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if available)

### What to Expect

- **Acknowledgment**: Within 48 hours
- **Assessment**: Within 7 days
- **Fix Timeline**: Varies by severity (critical issues prioritized)
- **Credit**: You'll be credited in the security advisory (unless you prefer to remain anonymous)

## Supported Versions

Security updates are provided for the latest version. We recommend always running the most recent release.

| Version | Supported          |
| ------- | ------------------ |
| Latest  | :white_check_mark: |
| Older   | :x:                |

## Security Best Practices

When contributing or deploying, please follow these practices:

### Authentication & Authorization

- Never commit API keys, tokens, or credentials
- Store sensitive data in `.env` (never committed)
- Use Laravel's built-in authentication mechanisms
- Respect middleware access controls (`admin`, `shop.access:read`, `shop.access:write`)

### Shopify Integration

- **HMAC Verification**: Always verify webhook signatures using shop-specific webhook secrets
- **API Credentials**: Store per-store credentials in database (`shopify_shops` table)
- **Rate Limiting**: Respect Shopify API rate limits
- **Token Security**: Never log or expose API tokens

### Database Security

- **Production Testing**: NEVER run tests against production MySQL database
  - Tests automatically use SQLite (enforced in `phpunit.xml` and `TestCase.php`)
  - This prevents accidental data loss
- **SQL Injection**: Use Eloquent ORM or parameterized queries
- **Sensitive Data**: Be careful with logging and error messages

### Multi-Tenant Security

- **Shop Isolation**: Verify shop access in controllers/middleware
- **User Permissions**: Check access levels before CRUD operations
- **Cross-Shop Data**: Never expose one shop's data to users of another shop

### Frontend Security

- **CSRF Protection**: Use Laravel's CSRF token (included in `fetchWrapper`)
- **XSS Prevention**: React escapes by default, but be careful with `dangerouslySetInnerHTML`
- **Input Validation**: Validate and sanitize user input on both frontend and backend
- **API Security**: Always use session authentication (`credentials: include`)

### Dependencies

- **Regular Updates**: Keep dependencies up to date
- **Vulnerability Scanning**: Monitor for security advisories
- **Composer/npm Audit**: Run `composer audit` and `pnpm audit` regularly

### Production Deployment

- **Environment Variables**: Never expose `.env` files
- **Debug Mode**: Set `APP_DEBUG=false` in production
- **HTTPS**: Always use HTTPS in production
- **Error Logging**: Don't expose stack traces to users
- **Webhook Secrets**: Use strong, unique secrets per store

## Known Security Considerations

### Multi-Tenant Data Isolation

The application uses a multi-tenant architecture with shop-based data isolation:

- Admin users (ID 1 or `is_admin=true`) have access to all shops
- Regular users must be explicitly granted shop access via `user_shop_accesses` table
- Middleware enforces access control at the route level
- Services are instantiated with shop-specific credentials

**Important**: When adding new features, always use the shop-scoped route pattern and appropriate middleware.

### Shopify Webhook Security

Incoming webhooks are verified using HMAC signatures:

1. Shop identified by `X-Shopify-Shop-Domain` header
2. HMAC calculated using shop-specific webhook secret
3. Request rejected if HMAC doesn't match

**Important**: Never process webhooks without HMAC verification.

### Database Testing Safety

Tests use SQLite to prevent accidental damage to production databases:

- Multiple safety checks in `TestCase.php` and `phpunit.xml`
- Exception thrown if MySQL is detected during tests
- `RefreshDatabase` trait safe to use with SQLite

**Important**: Never modify test configuration to use MySQL.

## Security Features

- ✅ Session-based authentication
- ✅ CSRF protection
- ✅ HMAC webhook verification
- ✅ Multi-tenant data isolation
- ✅ Role-based access control (Admin, Read-Write, Read-Only)
- ✅ Shop-scoped API credentials
- ✅ SQL injection protection via Eloquent
- ✅ XSS protection via React
- ✅ Test database isolation (SQLite)

## Disclosure Policy

When a security issue is fixed:

1. A security advisory will be published
2. The fix will be released in a new version
3. Users will be notified to upgrade
4. Details will be made public after users have had time to update

## Questions?

If you have questions about security practices in this project, please open a discussion (for general questions) or contact the maintainers privately (for sensitive matters).

---

Thank you for helping keep UC Laravel secure! 🔒
