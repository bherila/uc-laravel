import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect, useCallback } from 'react';
import Container from '@/components/container';
import MainTitle from '@/components/MainTitle';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { fetchWrapper } from '@/fetchWrapper';
import { format } from 'date-fns';
import { Eye, CheckCircle, XCircle, AlertCircle, RefreshCw } from 'lucide-react';
import Link from '@/components/link';

interface Webhook {
  id: number;
  rerun_of_id: number | null;
  created_at: string;
  payload: string | null;
  headers: string | null;
  shopify_topic: string | null;
  shop_id: number | null;
  shop: {
      id: number;
      name: string;
      shop_domain: string;
  } | null;
  valid_hmac: boolean | null;
  valid_shop_matched: boolean | null;
  error_ts: string | null;
  success_ts: string | null;
  error_message: string | null;
}

interface Shop {
    id: number;
    name: string;
    shop_domain: string;
}

interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
  next_page_url: string | null;
  prev_page_url: string | null;
}

function AdminWebhooksPage() {
  const [data, setData] = useState<PaginatedResponse<Webhook> | null>(null);
  const [shops, setShops] = useState<Shop[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const [selectedShopId, setSelectedShopId] = useState<string>('all');
  
  const apiBase = document.getElementById('admin-webhooks-root')?.dataset.apiBase || '/api';

  const fetchWebhooks = useCallback(async (pageNum: number, shopId: string) => {
    setLoading(true);
    try {
      const queryParams = new URLSearchParams({
          page: pageNum.toString(),
          shop_id: shopId
      });
      const result = await fetchWrapper.get(`${apiBase}/admin/webhooks?${queryParams.toString()}`);
      setData(result);
      setPage(pageNum);
    } catch (err) {
      setError('Failed to load webhooks');
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [apiBase]);

  const fetchShops = useCallback(async () => {
      try {
          const result = await fetchWrapper.get(`${apiBase}/admin/stores`);
          setShops(result);
      } catch (err) {
          console.error('Failed to load shops', err);
      }
  }, [apiBase]);

  useEffect(() => {
    fetchShops();
    fetchWebhooks(1, 'all');
  }, [fetchShops, fetchWebhooks]);

  const handleShopChange = (value: string) => {
      setSelectedShopId(value);
      fetchWebhooks(1, value);
  };

  const getSizeInKB = (str: string | null) => {
    if (!str) return '0 KB';
    return (new Blob([str]).size / 1024).toFixed(2) + ' KB';
  };

  const getStatusBadge = (webhook: Webhook) => {
    if (webhook.success_ts) {
        return <Badge className="bg-green-500 hover:bg-green-600"><CheckCircle className="w-3 h-3 mr-1" /> Success</Badge>;
    }
    if (webhook.error_ts) {
        return <Badge variant="destructive"><XCircle className="w-3 h-3 mr-1" /> Error</Badge>;
    }
    return <Badge variant="secondary"><AlertCircle className="w-3 h-3 mr-1" /> Pending</Badge>;
  };

  if (loading && !data) {
    return (
      <Container>
        <MainTitle>Webhooks</MainTitle>
        <div className="space-y-4">
          {[1, 2, 3, 4, 5].map((i) => (
            <Skeleton key={i} className="h-12 w-full" />
          ))}
        </div>
      </Container>
    );
  }

  if (error) {
    return (
      <Container>
        <MainTitle>Webhooks</MainTitle>
        <div className="text-red-600">{error}</div>
      </Container>
    );
  }

  return (
    <Container>
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <MainTitle>Webhooks</MainTitle>
        <div className="flex items-center gap-1">
            <div className="w-64">
                <Select value={selectedShopId} onValueChange={handleShopChange}>
                    <SelectTrigger>
                        <SelectValue placeholder="Filter by Shop" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">(All)</SelectItem>
                        <SelectItem value="unmatched">(Unmatched)</SelectItem>
                        {shops.map(shop => (
                            <SelectItem key={shop.id} value={shop.id.toString()}>
                                {shop.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <Button variant="outline" size="sm" onClick={() => fetchWebhooks(page, selectedShopId)}>
                <RefreshCw className="w-4 h-4 mr-2" /> Refresh
            </Button>
        </div>
      </div>
      
      <div className="rounded-md border border-gray-200 dark:border-gray-700 overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>ID</TableHead>
              <TableHead>Received At</TableHead>
              <TableHead>Topic</TableHead>
              <TableHead>Payload Size</TableHead>
              <TableHead>Headers Size</TableHead>
              <TableHead>HMAC</TableHead>
              <TableHead>Shop</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Rerun Of</TableHead>
              <TableHead></TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {data?.data.map((webhook) => (
              <TableRow key={webhook.id}>
                <TableCell>{webhook.id}</TableCell>
                <TableCell className="text-sm">
                  {format(new Date(webhook.created_at), 'yyyy-MM-dd HH:mm:ss')}
                </TableCell>
                <TableCell className="text-sm">
                  {webhook.shopify_topic || '-'}
                </TableCell>
                <TableCell>{getSizeInKB(webhook.payload)}</TableCell>
                <TableCell>{getSizeInKB(webhook.headers)}</TableCell>
                <TableCell>
                  {webhook.valid_hmac === true && <CheckCircle className="w-5 h-5 text-green-600" />}
                  {webhook.valid_hmac === false && <XCircle className="w-5 h-5 text-red-600" />}
                  {webhook.valid_hmac === null && <span className="text-gray-400">-</span>}
                </TableCell>
                <TableCell>
                  {webhook.shop ? (
                      <div className="flex flex-col">
                          <span className="font-medium">{webhook.shop.name}</span>
                          <span className="text-xs text-gray-500">{webhook.shop.shop_domain}</span>
                      </div>
                  ) : (
                      webhook.valid_shop_matched ? <span className="text-gray-500">Legacy Match</span> : <span className="text-red-400">-</span>
                  )}
                </TableCell>
                <TableCell>
                  {getStatusBadge(webhook)}
                </TableCell>
                <TableCell>
                  {webhook.rerun_of_id ? (
                      <Link href={`/admin/webhooks/${webhook.rerun_of_id}`} className="text-blue-600 hover:underline">
                          #{webhook.rerun_of_id}
                      </Link>
                  ) : '-'}
                </TableCell>
                <TableCell>
                  <Button variant="ghost" size="sm" asChild>
                    <a href={`/admin/webhooks/${webhook.id}`}>
                      <Eye className="w-4 h-4" />
                    </a>
                  </Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>

      {data && (
        <div className="flex items-center justify-between mt-4">
          <div className="text-sm text-gray-500">
            Page {data.current_page} of {data.last_page} (Total {data.total})
          </div>
          <div className="flex gap-2">
            <Button 
              variant="outline" 
              size="sm" 
              onClick={() => fetchWebhooks(page - 1, selectedShopId)}
              disabled={!data.prev_page_url || loading}
            >
              Previous
            </Button>
            <Button 
              variant="outline" 
              size="sm" 
              onClick={() => fetchWebhooks(page + 1, selectedShopId)}
              disabled={!data.next_page_url || loading}
            >
              Next
            </Button>
          </div>
        </div>
      )}
    </Container>
  );
}

const root = document.getElementById('admin-webhooks-root');
if (root) {
  createRoot(root).render(<AdminWebhooksPage />);
}
