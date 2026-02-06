## Description

<!-- Provide a brief description of the changes in this PR -->

## Type of Change

<!-- Mark the relevant option with an "x" -->

- [ ] Bug fix (non-breaking change that fixes an issue)
- [ ] New feature (non-breaking change that adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update
- [ ] Code refactoring
- [ ] Performance improvement
- [ ] Test addition/update

## Related Issues

<!-- Link to related issues using #issue_number -->

Closes #

## Changes Made

<!-- List the specific changes made in this PR -->

- 
- 
- 

## Testing

<!-- Describe the tests you ran and their results -->

### PHP Tests
- [ ] All existing tests pass (`composer test`)
- [ ] Added new tests for this change
- [ ] Verified SQLite schema is updated (if database changes)

### TypeScript Tests
- [ ] All existing tests pass (`pnpm test`)
- [ ] Added new tests for this change

### Manual Testing
- [ ] Tested locally with `composer dev`
- [ ] Verified UI changes (attach screenshot if applicable)
- [ ] Tested multi-tenant scenarios (if applicable)
- [ ] Tested Shopify integration (if applicable)

## Checklist

- [ ] My code follows the project's code style
- [ ] I have reviewed `.github/copilot-instructions.md` for project patterns
- [ ] I have added/updated tests that prove my fix/feature works
- [ ] I have updated documentation (README.md, copilot-instructions.md) if needed
- [ ] I have updated `database/schema/sqlite-schema.sql` if I added database migrations
- [ ] My changes generate no new warnings or errors
- [ ] All tests pass locally
- [ ] I have checked my code for security vulnerabilities
- [ ] I have followed multi-tenant patterns (shop-scoped routes, middleware)

## Multi-Tenant Considerations

<!-- Only fill this section if your changes affect multi-tenant functionality -->

- [ ] N/A - This change doesn't affect multi-tenant features
- [ ] Routes are shop-scoped with appropriate middleware
- [ ] Services are instantiated with shop-specific credentials
- [ ] Access control is properly enforced
- [ ] Tested with multiple shops/users with different access levels

## Shopify Integration

<!-- Only fill this section if your changes affect Shopify integration -->

- [ ] N/A - This change doesn't affect Shopify integration
- [ ] Used appropriate service classes (ShopifyProductService, etc.)
- [ ] Respected caching strategy (1-hour TTL)
- [ ] Webhook HMAC verification is maintained
- [ ] Tested with Shopify test store

## Screenshots

<!-- Add screenshots here if you made UI changes -->

## Additional Context

<!-- Add any other context about the PR here -->

## Breaking Changes

<!-- If this is a breaking change, describe what breaks and how to migrate -->

---

**Reviewer Notes:**
<!-- Add any notes for reviewers here -->
