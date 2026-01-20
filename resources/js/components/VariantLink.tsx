import React from 'react';
import { ExternalLink } from 'lucide-react';

interface Props {
  variantId: string;
  productId?: string | undefined;
  shopDomain?: string | undefined;
  className?: string;
}

/**
 * Renders a link to a Shopify Product (preferring the product page over the variant page)
 */
export default function VariantLink({ variantId, productId, shopDomain, className = "" }: Props) {
  const shopSlug = shopDomain?.replace('.myshopify.com', '') || 'underground-cellar';
  
  const variantNumericId = variantId.replace('gid://shopify/ProductVariant/', '');
  
  let href: string;
  if (productId) {
    const productNumericId = productId.replace('gid://shopify/Product/', '');
    href = `https://admin.shopify.com/store/${shopSlug}/products/${productNumericId}`;
  } else {
    href = `https://admin.shopify.com/store/${shopSlug}/products/variants/${variantNumericId}`;
  }

  return (
    <a
      href={href}
      target="_blank"
      rel="noopener noreferrer"
      className={`text-xs text-muted-foreground hover:underline font-mono inline-flex items-center gap-1 ${className}`}
    >
      {variantNumericId}
      <ExternalLink className="w-3 h-3" />
    </a>
  );
}
