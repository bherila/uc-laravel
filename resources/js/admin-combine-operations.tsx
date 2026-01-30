import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect, useCallback } from 'react';
import Container from '@/components/container';
import MainTitle from '@/components/MainTitle';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { fetchWrapper } from '@/fetchWrapper';
import { format } from 'date-fns';
import { Eye, CheckCircle, XCircle, AlertCircle, RefreshCw, Search } from 'lucide-react';
import Link from '@/components/link';
import { SimplePagination } from '@/components/SimplePagination';

interface CombineOperation {
  id: number;
  audit_log_id: number | null;
  shop_id: number | null;
  order_id: string;
  order_id_numeric: number | null;
  user_id: number | null;
  status: string;
  error_message: string | null;
  original_shipping_method: string | null;
  fulfillment_orders_before: number | null;
  fulfillment_orders_after: number | null;
  created_at: string;
  updated_at: string;
  shop?: {
    id: number;
    name: string;
  } | null;
  user?: {
    id: number;
    email: string;
  } | null;
}

interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
}

function AdminCombineOperationsPage() {
  const [data, setData] = useState<PaginatedResponse<CombineOperation> | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searchText, setSearchText] = useState('');
  const [page, setPage] = useState(1);
  const [inputValue, setInputValue] = useState('');
  
  const apiBase = document.getElementById('admin-combine-operations-root')?.dataset.apiBase || '/api';

  const fetchOperations = useCallback(async (pageNum: number, search: string) => {
    setLoading(true);
    try {
      const params = new URLSearchParams();
      if (pageNum > 1) params.set('page', pageNum.toString());
      if (search) params.set('search', search);

      const result = await fetchWrapper.get(`${apiBase}/admin/combine-operations?${params.toString()}`);
      setData(result);
    } catch (err) {
      setError('Failed to load combine operations');
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [apiBase]);

  useEffect(() => {
    fetchOperations(page, searchText);
  }, [page, searchText, fetchOperations]);

  const onSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setSearchText(inputValue);
    setPage(1);
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'success':
        return <Badge className="bg-green-500 hover:bg-green-600"><CheckCircle className="w-3 h-3 mr-1" /> Success</Badge>;
      case 'error':
        return <Badge variant="destructive"><XCircle className="w-3 h-3 mr-1" /> Error</Badge>;
      case 'pending':
        return <Badge variant="secondary"><AlertCircle className="w-3 h-3 mr-1" /> Pending</Badge>;
      default:
        return <Badge variant="secondary">{status}</Badge>;
    }
  };

  function getShopifyOrderUrl(orderId: string): string {
    const numericId = orderId.replace('gid://shopify/Order/', '');
    return `https://admin.shopify.com/store/underground-cellar/orders/${numericId}`;
  }

  if (loading && !data) {
    return (
      <Container>
        <MainTitle>Combine Operations</MainTitle>
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
        <MainTitle>Combine Operations</MainTitle>
        <div className="text-red-600">{error}</div>
      </Container>
    );
  }

  return (
    <Container>
      <div className="flex items-center justify-between mb-6">
        <MainTitle>Combine Operations</MainTitle>
        <Button variant="outline" size="sm" onClick={() => fetchOperations(page, searchText)}>
          <RefreshCw className="w-4 h-4 mr-2" /> Refresh
        </Button>
      </div>

      <form onSubmit={onSearchSubmit} className="flex gap-2 mb-6">
        <div className="relative flex-1 max-w-md">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            type="text"
            placeholder="Search by order ID, status, shipping method..."
            value={inputValue}
            onChange={(e) => setInputValue(e.target.value)}
            className="pl-10"
          />
        </div>
        <Button type="submit">Search</Button>
        {searchText && (
          <Button type="button" variant="outline" onClick={() => { setInputValue(''); setSearchText(''); setPage(1); }}>
            Clear
          </Button>
        )}
      </form>
      
      <div className="rounded-md border border-gray-200 dark:border-gray-700 overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>ID</TableHead>
              <TableHead>Created At</TableHead>
              <TableHead>Order</TableHead>
              <TableHead>Shop</TableHead>
              <TableHead>User</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Original Shipping</TableHead>
              <TableHead>Before → After</TableHead>
              <TableHead></TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {data?.data.map((op) => (
              <TableRow key={op.id}>
                <TableCell>{op.id}</TableCell>
                <TableCell className="text-sm">
                  {format(new Date(op.created_at), 'yyyy-MM-dd HH:mm:ss')}
                </TableCell>
                <TableCell>
                  <a 
                    href={getShopifyOrderUrl(op.order_id)} 
                    target="_blank" 
                    rel="noopener noreferrer"
                    className="font-mono text-sm hover:underline text-blue-600"
                  >
                    #{op.order_id_numeric}
                  </a>
                </TableCell>
                <TableCell>{op.shop?.name || '-'}</TableCell>
                <TableCell className="text-sm truncate max-w-[150px]" title={op.user?.email}>
                  {op.user?.email || '-'}
                </TableCell>
                <TableCell>{getStatusBadge(op.status)}</TableCell>
                <TableCell className="text-sm truncate max-w-[150px]" title={op.original_shipping_method || undefined}>
                  {op.original_shipping_method || '-'}
                </TableCell>
                <TableCell className="text-sm">
                  {op.fulfillment_orders_before ?? '-'} → {op.fulfillment_orders_after ?? '-'}
                </TableCell>
                <TableCell>
                  <Link href={`/admin/combine-operations/${op.id}`}>
                    <Button variant="outline" size="sm">
                      <Eye className="w-4 h-4" />
                    </Button>
                  </Link>
                </TableCell>
              </TableRow>
            ))}
            {data?.data.length === 0 && (
              <TableRow>
                <TableCell colSpan={9} className="text-center py-8 text-muted-foreground">
                  No combine operations found.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      {data && data.last_page > 1 && (
        <div className="mt-4">
          <SimplePagination
            currentPage={data.current_page}
            lastPage={data.last_page}
            onPageChange={setPage}
          />
        </div>
      )}
    </Container>
  );
}

const element = document.getElementById('admin-combine-operations-root');
if (element) {
  createRoot(element).render(<AdminCombineOperationsPage />);
}
