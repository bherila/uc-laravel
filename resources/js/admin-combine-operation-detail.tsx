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
import { ArrowLeft, Copy, CheckCircle, XCircle, AlertCircle, ExternalLink, FileJson } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogDescription } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';

interface CombineOperationLog {
  id: number;
  combine_operation_id: number;
  event: string | null;
  time_taken_ms: number | null;
  shopify_request: string | null;
  shopify_response: string | null;
  shopify_response_code: number | null;
  created_at: string;
}

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
    shop_domain: string;
  } | null;
  user?: {
    id: number;
    email: string;
  } | null;
  logs: CombineOperationLog[];
  audit_log?: {
    id: number;
    event_name: string;
    event_ext: string | null;
  } | null;
}

function AdminCombineOperationDetailPage() {
  const [operation, setOperation] = useState<CombineOperation | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  const rootEl = document.getElementById('admin-combine-operation-detail-root');
  const apiBase = rootEl?.dataset.apiBase || '/api';
  const operationId = rootEl?.dataset.id;

  const fetchOperation = useCallback(async () => {
    if (!operationId) return;
    setLoading(true);
    try {
      const data = await fetchWrapper.get(`${apiBase}/admin/combine-operations/${operationId}`);
      setOperation(data);
    } catch (err) {
      setError('Failed to load combine operation');
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [apiBase, operationId]);

  useEffect(() => {
    fetchOperation();
  }, [fetchOperation]);

  const handleCopy = (text: string) => {
    navigator.clipboard.writeText(text);
  };

  const formatJson = (str: string | null) => {
    if (!str) return '';
    try {
      return JSON.stringify(JSON.parse(str), null, 2);
    } catch (e) {
      return str;
    }
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

  function getShopifyOrderUrl(orderId: string, shopDomain?: string): string {
    const numericId = orderId.replace('gid://shopify/Order/', '');
    const shopSlug = shopDomain?.replace('.myshopify.com', '') || 'underground-cellar';
    return `https://admin.shopify.com/store/${shopSlug}/orders/${numericId}`;
  }

  if (loading) {
    return (
      <Container>
        <div className="space-y-4">
          <Skeleton className="h-8 w-1/3" />
          <Skeleton className="h-64 w-full" />
        </div>
      </Container>
    );
  }

  if (error || !operation) {
    return (
      <Container>
        <div className="text-red-600">{error || 'Combine operation not found'}</div>
      </Container>
    );
  }

  return (
    <Container>
      <div className="mb-6">
        <Button variant="ghost" size="sm" asChild className="mb-4 pl-0 hover:pl-0">
          <a href="/admin/combine-operations">
            <ArrowLeft className="w-4 h-4 mr-2" /> Back to Combine Operations
          </a>
        </Button>
        
        <div className="flex items-center justify-between">
          <MainTitle>Combine Operation #{operation.id}</MainTitle>
          {getStatusBadge(operation.status)}
        </div>
      </div>

      {/* Summary Card */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div className="border rounded-md p-4">
          <div className="text-sm text-muted-foreground">Order</div>
          <a 
            href={getShopifyOrderUrl(operation.order_id, operation.shop?.shop_domain)} 
            target="_blank" 
            rel="noopener noreferrer"
            className="text-lg font-semibold hover:underline text-blue-600 flex items-center gap-1"
          >
            #{operation.order_id_numeric}
            <ExternalLink className="w-4 h-4" />
          </a>
        </div>
        <div className="border rounded-md p-4">
          <div className="text-sm text-muted-foreground">Shop</div>
          <div className="text-lg font-semibold">{operation.shop?.name || '-'}</div>
        </div>
        <div className="border rounded-md p-4">
          <div className="text-sm text-muted-foreground">User</div>
          <div className="text-lg font-semibold truncate" title={operation.user?.email}>{operation.user?.email || '-'}</div>
        </div>
        <div className="border rounded-md p-4">
          <div className="text-sm text-muted-foreground">Created At</div>
          <div className="text-lg font-semibold">{format(new Date(operation.created_at), 'yyyy-MM-dd HH:mm:ss')}</div>
        </div>
      </div>

      {/* Details */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div className="border rounded-md p-4">
          <div className="text-sm text-muted-foreground mb-2">Original Shipping Method</div>
          <div className="font-semibold">{operation.original_shipping_method || <span className="text-muted-foreground">Not identified</span>}</div>
        </div>
        <div className="border rounded-md p-4">
          <div className="text-sm text-muted-foreground mb-2">Fulfillment Orders</div>
          <div className="font-semibold">
            {operation.fulfillment_orders_before ?? '-'} → {operation.fulfillment_orders_after ?? '-'}
            {operation.fulfillment_orders_before !== null && operation.fulfillment_orders_after !== null && (
              <span className="ml-2 text-sm text-muted-foreground">
                ({operation.fulfillment_orders_before > operation.fulfillment_orders_after ? 'merged' : 'no change'})
              </span>
            )}
          </div>
        </div>
      </div>

      {operation.error_message && (
        <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md p-4 mb-6">
          <div className="flex items-start gap-2">
            <XCircle className="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" />
            <div>
              <div className="font-semibold text-red-700 dark:text-red-300">Error</div>
              <div className="text-sm text-red-600 dark:text-red-400 whitespace-pre-wrap">{operation.error_message}</div>
            </div>
          </div>
        </div>
      )}

      {operation.audit_log_id && (
        <div className="mb-6">
          <a href={`/admin/audit-logs?search=${operation.order_id_numeric}`} className="text-blue-600 hover:underline text-sm">
            View related audit log entries →
          </a>
        </div>
      )}

      {/* Event Log */}
      <h2 className="text-lg font-semibold mb-4">Event Log</h2>
      <div className="rounded-md border border-gray-200 dark:border-gray-700 overflow-x-auto mb-8">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Time (ms)</TableHead>
              <TableHead>Event</TableHead>
              <TableHead>Shopify API</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {operation.logs.map((log) => (
              <TableRow key={log.id}>
                <TableCell className="text-sm font-mono">
                  {log.time_taken_ms !== null ? `+${log.time_taken_ms}ms` : '-'}
                </TableCell>
                <TableCell className="text-sm max-w-[500px]">
                  <div className="whitespace-pre-wrap break-words">{log.event}</div>
                </TableCell>
                <TableCell>
                  {(log.shopify_request || log.shopify_response) ? (
                    <Dialog>
                      <DialogTrigger asChild>
                        <Button variant="outline" size="sm">
                          <FileJson className="w-4 h-4 mr-1" /> View
                        </Button>
                      </DialogTrigger>
                      <DialogContent className="max-w-4xl max-h-[80vh] overflow-auto">
                        <DialogHeader>
                          <DialogTitle>Shopify API Call</DialogTitle>
                          <DialogDescription>Request and response data for this API call</DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4 py-4">
                          {log.shopify_request && (
                            <div>
                              <div className="flex justify-between items-center mb-2">
                                <h4 className="font-semibold">Request</h4>
                                <Button variant="outline" size="sm" onClick={() => handleCopy(formatJson(log.shopify_request))}>
                                  <Copy className="w-4 h-4 mr-1" /> Copy
                                </Button>
                              </div>
                              <Textarea
                                readOnly
                                className="font-mono text-xs h-48"
                                value={formatJson(log.shopify_request)}
                              />
                            </div>
                          )}
                          {log.shopify_response && (
                            <div>
                              <div className="flex justify-between items-center mb-2">
                                <h4 className="font-semibold">Response</h4>
                                <Button variant="outline" size="sm" onClick={() => handleCopy(formatJson(log.shopify_response))}>
                                  <Copy className="w-4 h-4 mr-1" /> Copy
                                </Button>
                              </div>
                              <Textarea
                                readOnly
                                className="font-mono text-xs h-48"
                                value={formatJson(log.shopify_response)}
                              />
                            </div>
                          )}
                        </div>
                      </DialogContent>
                    </Dialog>
                  ) : (
                    <span className="text-muted-foreground text-sm">-</span>
                  )}
                </TableCell>
              </TableRow>
            ))}
            {operation.logs.length === 0 && (
              <TableRow>
                <TableCell colSpan={3} className="text-center py-8 text-muted-foreground">
                  No events logged.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>
    </Container>
  );
}

const element = document.getElementById('admin-combine-operation-detail-root');
if (element) {
  createRoot(element).render(<AdminCombineOperationDetailPage />);
}
