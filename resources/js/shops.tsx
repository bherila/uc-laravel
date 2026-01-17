import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect, useCallback } from 'react';
import Container from '@/components/container';
import MainTitle from '@/components/MainTitle';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { fetchWrapper } from '@/fetchWrapper';
import { Store, ArrowRight } from 'lucide-react';

interface Shop {
  id: number;
  name: string;
  shop_domain: string;
  offers_count: number;
  access_level: 'read-only' | 'read-write';
}

function ShopsPage() {
  const [shops, setShops] = useState<Shop[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  const apiBase = document.getElementById('shops-root')?.dataset.apiBase || '/api';

  const fetchShops = useCallback(async () => {
    try {
      const data = await fetchWrapper.get(`${apiBase}/shops`);
      setShops(data);
    } catch (err) {
      setError('Failed to load shops');
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [apiBase]);

  useEffect(() => {
    fetchShops();
  }, [fetchShops]);

  if (loading) {
    return (
      <Container>
        <MainTitle>Shops</MainTitle>
        <div className="space-y-4">
          {[1, 2, 3].map((i) => (
            <Skeleton key={i} className="h-16 w-full" />
          ))}
        </div>
      </Container>
    );
  }

  if (error) {
    return (
      <Container>
        <MainTitle>Shops</MainTitle>
        <div className="text-red-600 dark:text-red-400">{error}</div>
      </Container>
    );
  }

  if (shops.length === 0) {
    return (
      <Container>
        <MainTitle>Shops</MainTitle>
        <div className="text-center py-12 text-gray-600 dark:text-gray-400">
          <Store className="w-12 h-12 mx-auto mb-4 opacity-50" />
          <p>No shops available. Contact an administrator to get access to a shop.</p>
        </div>
      </Container>
    );
  }

  return (
    <Container>
      <MainTitle>Shops</MainTitle>
      
      <div className="rounded-md border border-gray-200 dark:border-gray-700">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Domain</TableHead>
              <TableHead>Offers</TableHead>
              <TableHead>Access</TableHead>
              <TableHead className="w-24"></TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {shops.map((shop) => (
              <TableRow key={shop.id}>
                <TableCell className="font-medium">{shop.name}</TableCell>
                <TableCell className="text-gray-600 dark:text-gray-400 text-sm">
                  {shop.shop_domain}
                </TableCell>
                <TableCell>
                  <Badge variant="secondary">{shop.offers_count} offers</Badge>
                </TableCell>
                <TableCell>
                  <Badge variant={shop.access_level === 'read-write' ? 'default' : 'outline'}>
                    {shop.access_level}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Button variant="ghost" size="sm" asChild>
                    <a href={`/shop/${shop.id}/offers`}>
                      <ArrowRight className="w-4 h-4" />
                    </a>
                  </Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    </Container>
  );
}

const root = document.getElementById('shops-root');
if (root) {
  createRoot(root).render(<ShopsPage />);
}
