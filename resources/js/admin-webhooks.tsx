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
import { format } from 'date-fns';
import { Eye, CheckCircle, XCircle, AlertCircle, RefreshCw } from 'lucide-react';
import Link from '@/components/link';

interface Webhook {
  id: number;
  rerun_of_id: number | null;
  created_at: string;
  payload: string | null;
  headers: string | null;
  valid_hmac: boolean | null;
  valid_shop_matched: boolean | null;
  error_ts: string | null;
  success_ts: string | null;
  error_message: string | null;
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
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  
  const apiBase = document.getElementById('admin-webhooks-root')?.dataset.apiBase || '/api';

  const fetchWebhooks = useCallback(async (pageNum: number) => {
    setLoading(true);
    try {
      const result = await fetchWrapper.get(`${apiBase}/admin/webhooks?page=${pageNum}`);
      setData(result);
      setPage(pageNum);
    } catch (err) {
      setError('Failed to load webhooks');
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [apiBase]);

  useEffect(() => {
    fetchWebhooks(1);
  }, [fetchWebhooks]);

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
      <div className="flex items-center justify-between mb-6">
        <MainTitle>Webhooks</MainTitle>
        <Button variant="outline" size="sm" onClick={() => fetchWebhooks(page)}>
            <RefreshCw className="w-4 h-4 mr-2" /> Refresh
        </Button>
      </div>
      
      <div className="rounded-md border border-gray-200 dark:border-gray-700 overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>ID</TableHead>
              <TableHead>Received At</TableHead>
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
                <TableCell>{getSizeInKB(webhook.payload)}</TableCell>
                <TableCell>{getSizeInKB(webhook.headers)}</TableCell>
                <TableCell>
                  {webhook.valid_hmac === true && <span className="text-green-600">Valid</span>}
                  {webhook.valid_hmac === false && <span className="text-red-600">Invalid</span>}
                  {webhook.valid_hmac === null && <span className="text-gray-400">-</span>}
                </TableCell>
                <TableCell>
                  {webhook.valid_shop_matched === true && <span className="text-green-600">Yes</span>}
                  {webhook.valid_shop_matched === false && <span className="text-red-600">No</span>}
                  {webhook.valid_shop_matched === null && <span className="text-gray-400">-</span>}
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
              onClick={() => fetchWebhooks(page - 1)}
              disabled={!data.prev_page_url || loading}
            >
              Previous
            </Button>
            <Button 
              variant="outline" 
              size="sm" 
              onClick={() => fetchWebhooks(page + 1)}
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
