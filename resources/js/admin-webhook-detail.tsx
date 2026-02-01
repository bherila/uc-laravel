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
import { ArrowLeft, Copy, CheckCircle, XCircle, AlertCircle, RefreshCw, FileJson, ExternalLink, Truck } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogDescription } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import Link from '@/components/link';

interface WebhookSub {
  id: number;
  created_at: string;
  event: string | null;
  offer_id: number | null;
  order_id: number | null;
  shopify_request: string | null;
  shopify_response: string | null;
  shopify_response_code: number | null;
  time_taken_ms: number | null;
  requested_query_cost: number | null;
  actual_query_cost: number | null;
  throttle_max: number | null;
  throttle_current: number | null;
  throttle_restore_rate: number | null;
  current_time_ms: number | null;
}

interface CombineOperationLog {
  id: number;
  event: string | null;
  time_taken_ms: number | null;
  shopify_request: string | null;
  shopify_response: string | null;
  created_at: string;
}

interface CombineOperation {
  id: number;
  order_id: string;
  order_id_numeric: number | null;
  status: string;
  original_shipping_method: string | null;
  fulfillment_orders_before: number | null;
  fulfillment_orders_after: number | null;
  error_message: string | null;
  created_at: string;
  logs: CombineOperationLog[];
}

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
  subs: WebhookSub[];
  rerun_of?: Webhook;
  combine_operations?: CombineOperation[];
}

function AdminWebhookDetailPage() {
  const [webhook, setWebhook] = useState<Webhook | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [rerunning, setRerunning] = useState(false);

  const rootEl = document.getElementById('admin-webhook-detail-root');
  const apiBase = rootEl?.dataset.apiBase || '/api';
  const webhookId = rootEl?.dataset.webhookId;

  const fetchWebhook = useCallback(async () => {
    if (!webhookId) return;
    setLoading(true);
    try {
      const data = await fetchWrapper.get(`${apiBase}/admin/webhooks/${webhookId}`);
      setWebhook(data);
    } catch (err) {
      setError('Failed to load webhook');
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [apiBase, webhookId]);

  useEffect(() => {
    fetchWebhook();
  }, [fetchWebhook]);

  const handleCopy = (text: string) => {
    navigator.clipboard.writeText(text);
    // Could add toast notification here
  };

  const handleRerun = async () => {
    if (!webhookId || !confirm('Are you sure you want to re-run this webhook?')) return;

    setRerunning(true);
    try {
      await fetchWrapper.post(`${apiBase}/admin/webhooks/${webhookId}/rerun`, {});
      alert('Webhook re-run initiated successfully.');
      // Refresh to see if there's any update? Or maybe redirect to list?
      // Since re-run creates a new webhook, maybe we should stay here or link to new one?
      // The requirement says "There will be a new webhook row created...".
      // We could refresh current page, but the new webhook is a new ID.
      // We can just alert for now.
    } catch (err) {
      console.error(err);
      alert('Failed to re-run webhook');
    } finally {
      setRerunning(false);
    }
  };

  const formatJson = (str: string | null) => {
    if (!str) return '';
    try {
      return JSON.stringify(JSON.parse(str), null, 2);
    } catch (e) {
      return str;
    }
  };

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

  if (error || !webhook) {
    return (
      <Container>
        <div className="text-red-600">{error || 'Webhook not found'}</div>
      </Container>
    );
  }

  return (
    <Container>
      <div className="mb-6">
        <Button variant="ghost" size="sm" asChild className="mb-4 pl-0 hover:pl-0">
          <a href="/admin/webhooks">
            <ArrowLeft className="w-4 h-4 mr-2" /> Back to Webhooks
          </a>
        </Button>

        <div className="flex items-center justify-between">
          <MainTitle>Webhook #{webhook.id}</MainTitle>
          <Button onClick={handleRerun} disabled={rerunning}>
            <RefreshCw className={`w-4 h-4 mr-2 ${rerunning ? 'animate-spin' : ''}`} />
            {rerunning ? 'Re-running...' : 'Re-run Webhook'}
          </Button>
        </div>
      </div>

      {webhook.rerun_of_id && (
        <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-4 mb-6 flex items-center">
          <AlertCircle className="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" />
          <span>
            This is a re-run of webhook <a href={`/admin/webhooks/${webhook.rerun_of_id}`} className="font-semibold underline">#{webhook.rerun_of_id}</a>.
          </span>
        </div>
      )}

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div className="space-y-4">
          <div className="p-4 rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <h3 className="font-semibold mb-4 text-lg">Details</h3>
            <div className="space-y-2 text-sm">
              <div className="flex justify-between">
                <span className="text-gray-500">Received At:</span>
                <span>{format(new Date(webhook.created_at), 'yyyy-MM-dd HH:mm:ss')}</span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-gray-500">Status:</span>
                <div>
                  {webhook.success_ts ? (
                    <Badge className="bg-green-500"><CheckCircle className="w-3 h-3 mr-1" /> Success</Badge>
                  ) : webhook.error_ts ? (
                    <Badge variant="destructive"><XCircle className="w-3 h-3 mr-1" /> Error</Badge>
                  ) : (
                    <Badge variant="secondary">Pending</Badge>
                  )}
                </div>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">HMAC Valid:</span>
                <span className={webhook.valid_hmac ? 'text-green-600' : 'text-red-600'}>
                  {webhook.valid_hmac ? 'Yes' : 'No'}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">Shop Matched:</span>
                <span className={webhook.valid_shop_matched ? 'text-green-600' : 'text-red-600'}>
                  {webhook.valid_shop_matched ? 'Yes' : 'No'}
                </span>
              </div>
              {webhook.error_message && (
                <div className="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                  <span className="text-red-600 block font-semibold">Error:</span>
                  <span className="text-red-600 block break-words">{webhook.error_message}</span>
                </div>
              )}
            </div>
          </div>
        </div>

        <div className="space-y-4">
          {/* Payload */}
          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <h4 className="font-semibold text-sm">Payload (JSON)</h4>
              <Button variant="ghost" size="sm" onClick={() => handleCopy(webhook.payload || '')}>
                <Copy className="w-3 h-3 mr-1" /> Copy
              </Button>
            </div>
            <Textarea
              readOnly
              value={formatJson(webhook.payload)}
              className="font-mono text-xs h-32 bg-gray-50 dark:bg-gray-900"
            />
          </div>
          {/* Headers */}
          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <h4 className="font-semibold text-sm">Headers</h4>
              <Button variant="ghost" size="sm" onClick={() => handleCopy(webhook.headers || '')}>
                <Copy className="w-3 h-3 mr-1" /> Copy
              </Button>
            </div>
            <Textarea
              readOnly
              value={formatJson(webhook.headers)} // Headers stored as JSON usually
              className="font-mono text-xs h-32 bg-gray-50 dark:bg-gray-900"
            />
          </div>
        </div>
      </div>

      <div className="space-y-4">
        <h3 className="font-semibold text-lg">Execution Log</h3>
        <div className="rounded-md border border-gray-200 dark:border-gray-700 overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-16">ID</TableHead>
                <TableHead className="w-48">Time</TableHead>
                <TableHead className="w-24">Offset (ms)</TableHead>
                <TableHead>Event</TableHead>
                <TableHead>Offer/Order</TableHead>
                <TableHead className="w-24">Shopify</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {webhook.subs.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={6} className="text-center text-gray-500 py-8">
                    No events logged.
                  </TableCell>
                </TableRow>
              ) : (
                webhook.subs.map((sub) => (
                  <TableRow key={sub.id}>
                    <TableCell>{sub.id}</TableCell>
                    <TableCell className="text-xs text-gray-500">
                      {format(new Date(sub.created_at), 'HH:mm:ss.SSS')}
                    </TableCell>
                    <TableCell className="text-xs text-gray-500 font-mono">
                      {sub.time_taken_ms !== null ? `+${sub.time_taken_ms}ms` : '-'}
                    </TableCell>
                    <TableCell>{sub.event || '-'}</TableCell>
                    <TableCell className="text-xs">
                      {sub.offer_id && <div>Offer: {sub.offer_id}</div>}
                      {sub.order_id && <div>Order: {sub.order_id}</div>}
                    </TableCell>
                    <TableCell>
                      {(sub.shopify_request || sub.shopify_response) && (
                        <Dialog>
                          <DialogTrigger asChild>
                            <Button variant="ghost" size="icon" className="h-8 w-8">
                              <FileJson className="w-4 h-4" />
                            </Button>
                          </DialogTrigger>
                          <DialogContent className="max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
                            <DialogHeader>
                              <DialogTitle>Shopify Interaction</DialogTitle>
                              <DialogDescription>
                                Details of the request sent to and response received from Shopify.
                                {sub.current_time_ms && (
                                  <div className="text-xs text-muted-foreground mt-1">
                                    Server Time: {format(new Date(sub.current_time_ms), 'yyyy-MM-dd HH:mm:ss.SSS')}
                                  </div>
                                )}
                              </DialogDescription>
                            </DialogHeader>

                            {(sub.actual_query_cost !== null || sub.throttle_current !== null) && (
                              <div className="grid grid-cols-2 md:grid-cols-5 gap-4 py-3 border-y my-2 bg-muted/30 rounded-sm px-4">
                                <div className="flex flex-col">
                                  <span className="text-[10px] uppercase text-muted-foreground font-bold">Requested Cost</span>
                                  <span className="font-mono text-sm">{sub.requested_query_cost ?? '-'}</span>
                                </div>
                                <div className="flex flex-col border-l pl-4">
                                  <span className="text-[10px] uppercase text-muted-foreground font-bold">Actual Cost</span>
                                  <span className="font-mono text-sm font-bold text-blue-600 dark:text-blue-400">{sub.actual_query_cost ?? '-'}</span>
                                </div>
                                <div className="flex flex-col border-l pl-4">
                                  <span className="text-[10px] uppercase text-muted-foreground font-bold">Throttle Max</span>
                                  <span className="font-mono text-sm">{sub.throttle_max ?? '-'}</span>
                                </div>
                                <div className="flex flex-col border-l pl-4">
                                  <span className="text-[10px] uppercase text-muted-foreground font-bold">Throttle Current</span>
                                  <span className="font-mono text-sm">{sub.throttle_current ?? '-'}</span>
                                </div>
                                <div className="flex flex-col border-l pl-4">
                                  <span className="text-[10px] uppercase text-muted-foreground font-bold">Restore Rate</span>
                                  <span className="font-mono text-sm">{sub.throttle_restore_rate ?? '-'}/s</span>
                                </div>
                              </div>
                            )}

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2 flex-1 overflow-hidden">
                              <div className="flex flex-col h-full overflow-hidden">
                                <div className="flex items-center justify-between mb-2">
                                  <h4 className="font-semibold text-sm">Request</h4>
                                  <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-7 px-2"
                                    onClick={() => handleCopy(sub.shopify_request || '')}
                                  >
                                    <Copy className="w-3 h-3 mr-1" /> Copy
                                  </Button>
                                </div>
                                <pre className="text-xs bg-gray-100 dark:bg-gray-900 p-2 rounded overflow-auto border flex-1 font-mono">
                                  {formatJson(sub.shopify_request)}
                                </pre>
                              </div>
                              <div className="flex flex-col h-full overflow-hidden">
                                <div className="flex items-center justify-between mb-2">
                                  <h4 className="font-semibold text-sm">
                                    Response
                                    {sub.shopify_response_code && (
                                      <Badge variant="outline" className="ml-2 py-0 h-5">
                                        {sub.shopify_response_code}
                                      </Badge>
                                    )}
                                  </h4>
                                  <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-7 px-2"
                                    onClick={() => handleCopy(sub.shopify_response || '')}
                                  >
                                    <Copy className="w-3 h-3 mr-1" /> Copy
                                  </Button>
                                </div>
                                <pre className="text-xs bg-gray-100 dark:bg-gray-900 p-2 rounded overflow-auto border flex-1 font-mono">
                                  {formatJson(sub.shopify_response)}
                                </pre>
                              </div>
                            </div>
                          </DialogContent>
                        </Dialog>
                      )}
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </div>
      </div>

      {/* Combine Operations Section */}
      {webhook.combine_operations && webhook.combine_operations.length > 0 && (
        <div className="space-y-4 mt-8">
          <h3 className="font-semibold text-lg flex items-center">
            <Truck className="w-5 h-5 mr-2" />
            Combine Shipping Operations
          </h3>
          <div className="rounded-md border border-gray-200 dark:border-gray-700 overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-16">ID</TableHead>
                  <TableHead>Order</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Shipping Method</TableHead>
                  <TableHead>Before/After</TableHead>
                  <TableHead className="w-24">Details</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {webhook.combine_operations.map((op) => (
                  <TableRow key={op.id}>
                    <TableCell>{op.id}</TableCell>
                    <TableCell className="text-xs">
                      <a
                        href={`/admin/combine-operations/${op.id}`}
                        className="text-blue-600 hover:underline"
                      >
                        Order #{op.order_id_numeric}
                      </a>
                    </TableCell>
                    <TableCell>
                      {op.status === 'success' ? (
                        <Badge className="bg-green-500"><CheckCircle className="w-3 h-3 mr-1" /> Success</Badge>
                      ) : op.status === 'error' ? (
                        <Badge variant="destructive"><XCircle className="w-3 h-3 mr-1" /> Error</Badge>
                      ) : (
                        <Badge variant="secondary">{op.status}</Badge>
                      )}
                    </TableCell>
                    <TableCell>{op.original_shipping_method || '-'}</TableCell>
                    <TableCell>
                      {op.fulfillment_orders_before !== null && op.fulfillment_orders_after !== null ? (
                        <span>{op.fulfillment_orders_before} → {op.fulfillment_orders_after}</span>
                      ) : '-'}
                    </TableCell>
                    <TableCell>
                      <Button variant="ghost" size="sm" asChild>
                        <a href={`/admin/combine-operations/${op.id}`}>
                          <ExternalLink className="w-4 h-4" />
                        </a>
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        </div>
      )}
    </Container>
  );
}

const root = document.getElementById('admin-webhook-detail-root');
if (root) {
  createRoot(root).render(<AdminWebhookDetailPage />);
}
