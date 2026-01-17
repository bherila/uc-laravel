import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect, useCallback } from 'react';
import Container from '@/components/container';
import MainTitle from '@/components/MainTitle';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { fetchWrapper } from '@/fetchWrapper';
import { Package, ArrowLeft, ArrowRight } from 'lucide-react';

interface Shop {
  id: number;
  name: string;
  shop_domain: string;
  offers_count: number;
  access_level: 'read-only' | 'read-write';
}

function ShopDashboardPage() {
  const [shop, setShop] = useState<Shop | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  const rootEl = document.getElementById('shop-dashboard-root');
  const apiBase = rootEl?.dataset.apiBase || '/api';
  const shopId = rootEl?.dataset.shopId;

  const fetchShop = useCallback(async () => {
    try {
      // Get shops list and find this one
      const shops = await fetchWrapper.get(`${apiBase}/shops`);
      const found = shops.find((s: Shop) => s.id === Number(shopId));
      if (!found) {
        setError('Shop not found or access denied');
        return;
      }
      setShop(found);
    } catch (err) {
      setError('Failed to load shop');
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [apiBase, shopId]);

  useEffect(() => {
    fetchShop();
  }, [fetchShop]);

  if (loading) {
    return (
      <Container>
        <MainTitle>Shop Dashboard</MainTitle>
        <Skeleton className="h-48 w-full" />
      </Container>
    );
  }

  if (error || !shop) {
    return (
      <Container>
        <MainTitle>Shop Dashboard</MainTitle>
        <div className="text-red-600 dark:text-red-400">{error || 'Shop not found'}</div>
      </Container>
    );
  }

  return (
    <Container>
      <div className="mb-6">
        <Button variant="ghost" size="sm" asChild>
          <a href="/shops">
            <ArrowLeft className="w-4 h-4 mr-2" />
            Back to Shops
          </a>
        </Button>
      </div>

      <MainTitle>{shop.name}</MainTitle>
      <p className="text-gray-600 dark:text-gray-400 mb-6">{shop.shop_domain}</p>
      
      <div className="flex items-center gap-2 mb-8">
        <Badge variant={shop.access_level === 'read-write' ? 'default' : 'outline'}>
          {shop.access_level}
        </Badge>
      </div>

      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {/* Offers Card */}
        <a 
          href={`/shop/${shop.id}/offers`}
          className="block p-6 border rounded-lg hover:border-gray-400 dark:hover:border-gray-500 transition-colors"
        >
          <div className="flex items-center justify-between mb-4">
            <Package className="w-8 h-8 text-gray-400" />
            <ArrowRight className="w-5 h-5 text-gray-400" />
          </div>
          <h3 className="text-lg font-semibold mb-1">Offers</h3>
          <p className="text-gray-600 dark:text-gray-400 text-sm mb-2">
            Manage wine offers and manifests
          </p>
          <Badge variant="secondary">{shop.offers_count} offers</Badge>
        </a>
      </div>
    </Container>
  );
}

const root = document.getElementById('shop-dashboard-root');
if (root) {
  createRoot(root).render(<ShopDashboardPage />);
}
