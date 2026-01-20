import React from 'react';
import { ChevronRight } from 'lucide-react';

interface Props {
  shopId: string | number;
  shopName?: string | undefined;
  offer?: {
    id: string | number;
    name: string;
  } | undefined;
  action?: string | undefined;
}

export default function ShopOfferBreadcrumb({ shopId, shopName, offer, action }: Props) {
  return (
    <div className="mb-6">
      <h1 className="text-xl md:text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100 flex flex-wrap items-center gap-2">
        <a href="/shops" className="text-muted-foreground hover:text-primary transition-colors">
          Shops
        </a>
        
        <ChevronRight className="w-4 h-4 text-muted-foreground/50" />
        
        {/* If we have an offer or an action, the shop name should be a link to the shop's offers list */}
        {(offer || action) ? (
          <a href={`/shop/${shopId}/offers`} className="text-muted-foreground hover:text-primary transition-colors">
            {shopName || `Shop ${shopId}`}
          </a>
        ) : (
          <span className="text-muted-foreground">{shopName || `Shop ${shopId}`}</span>
        )}

        <ChevronRight className="w-4 h-4 text-muted-foreground/50" />

        {/* If we have an action, the offer name should be a link to the offer detail */}
        {offer ? (
          action ? (
            <>
              <a href={`/shop/${shopId}/offers`} className="text-muted-foreground hover:text-primary transition-colors">
                Offers
              </a>
              <ChevronRight className="w-4 h-4 text-muted-foreground/50" />
              <a href={`/shop/${shopId}/offers/${offer.id}`} className="text-muted-foreground hover:text-primary transition-colors">
                [{offer.id}] {offer.name}
              </a>
              <ChevronRight className="w-4 h-4 text-muted-foreground/50" />
              <span>{action}</span>
            </>
          ) : (
            <>
              <span className="text-muted-foreground">Offers</span>
              <ChevronRight className="w-4 h-4 text-muted-foreground/50" />
              <span>[{offer.id}] {offer.name}</span>
            </>
          )
        ) : (
          <span>Offers</span>
        )}
      </h1>
    </div>
  );
}
