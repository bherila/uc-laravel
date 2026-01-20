import './bootstrap';
import { createRoot } from 'react-dom/client';
import React, { useState, useEffect, useCallback } from 'react';
import Container from '@/components/container';
import MainTitle from '@/components/MainTitle';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { fetchWrapper } from '@/fetchWrapper';
import { format } from 'date-fns';
import { Search } from 'lucide-react';
import { AuditLogPagination } from '@/components/AuditLogPagination';
import { AuditLogDetailCell } from '@/components/AuditLogDetailCell';

interface AuditLog {
  id: number;
  event_ts: string;
  event_name: string;
  event_ext: string | null;
  event_userid: number | null;
  offer_id: number | null;
  order_id: number | null;
  time_taken_ms: number | null;
}

interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
}

function AdminAuditLogsPage() {
  const [data, setData] = useState<PaginatedResponse<AuditLog> | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  // State for search query and pagination
  const [searchText, setSearchText] = useState('');
  const [page, setPage] = useState(1);
  const [inputValue, setInputValue] = useState('');

  const rootEl = document.getElementById('admin-audit-logs-root');
  const apiBase = rootEl?.dataset.apiBase || '/api';

  // Initialize state from URL params
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const pageParam = params.get('page');
    const searchParam = params.get('search');
    
    if (pageParam) setPage(parseInt(pageParam, 10));
    if (searchParam) setSearchText(searchParam);
  }, []);

  const fetchLogs = useCallback(async (pageNum: number, search: string) => {
    setLoading(true);
    try {
      // Update URL without reload
      const params = new URLSearchParams();
      if (pageNum > 1) params.set('page', pageNum.toString());
      if (search) params.set('search', search);
      
      const newUrl = `${window.location.pathname}${params.toString() ? '?' + params.toString() : ''}`;
      window.history.replaceState({}, '', newUrl);

      const queryParams = new URLSearchParams({
        page: pageNum.toString(),
        ...(search && { search })
      });

      const result = await fetchWrapper.get(`${apiBase}/admin/audit-logs?${queryParams.toString()}`);
      setData(result);
    } catch (err) {
      setError('Failed to load audit logs');
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [apiBase]);

  // Initial fetch and on page/search change
  useEffect(() => {
    fetchLogs(page, searchText);
  }, [page, searchText, fetchLogs]);

  // Sync input value with URL param on mount
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const searchParam = params.get('search');
    if (searchParam) setInputValue(searchParam);
  }, []);

  const onSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setSearchText(inputValue);
    setPage(1);
  };

  const handlePageChange = (newPage: number) => {
    setPage(newPage);
  };

  return (
    <Container>
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <MainTitle>Audit Log</MainTitle>
        
        <form onSubmit={onSearchSubmit} className="flex gap-2 w-full md:w-auto">
          <Input 
            type="text" 
            placeholder="Search logs..." 
            value={inputValue}
            onChange={(e) => setInputValue(e.target.value)}
            className="w-full md:w-64"
          />
          <Button type="submit" variant="secondary">
            <Search className="w-4 h-4 mr-2" />
            Search
          </Button>
        </form>
      </div>

      <div className="flex justify-end mb-4">
        {data && (
            <AuditLogPagination 
                currentPage={data.current_page} 
                lastPage={data.last_page} 
                onPageChange={handlePageChange}
                loading={loading}
            />
        )}
      </div>

      {error ? (
        <div className="text-red-600 p-4 border border-red-200 rounded-md">{error}</div>
      ) : (
        <div className="rounded-md border border-gray-200 dark:border-gray-700 overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-20">ID</TableHead>
                <TableHead className="w-40">Time</TableHead>
                <TableHead className="w-24">User</TableHead>
                <TableHead className="w-40">Event</TableHead>
                <TableHead>Details</TableHead>
                <TableHead className="w-24">Refs</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {loading && !data ? (
                Array.from({ length: 5 }).map((_, i) => (
                  <TableRow key={i}>
                    <TableCell><Skeleton className="h-4 w-8" /></TableCell>
                    <TableCell><Skeleton className="h-4 w-24" /></TableCell>
                    <TableCell><Skeleton className="h-4 w-20" /></TableCell>
                    <TableCell><Skeleton className="h-4 w-24" /></TableCell>
                    <TableCell><Skeleton className="h-4 w-full" /></TableCell>
                    <TableCell><Skeleton className="h-4 w-12" /></TableCell>
                  </TableRow>
                ))
              ) : data?.data.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={6} className="text-center py-8 text-gray-500">
                    No audit logs found.
                  </TableCell>
                </TableRow>
              ) : (
                data?.data.map((log) => (
                  <TableRow key={log.id}>
                    <TableCell>{log.id}</TableCell>
                    <TableCell className="text-xs text-gray-500 whitespace-nowrap">
                      {format(new Date(log.event_ts), 'MMM d, HH:mm:ss')}
                    </TableCell>
                    <TableCell className="text-sm font-medium">{log.event_userid || '-'}</TableCell>
                    <TableCell className="text-sm">{log.event_name}</TableCell>
                    <TableCell className="text-sm">
                        <AuditLogDetailCell detail={log.event_ext} />
                    </TableCell>
                    <TableCell className="text-xs">
                        {log.offer_id && <div className="whitespace-nowrap">Offer: {log.offer_id}</div>}
                        {log.order_id && <div className="whitespace-nowrap">Order: {log.order_id}</div>}
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </div>
      )}

      <div className="flex justify-end mt-4">
        {data && (
            <AuditLogPagination 
                currentPage={data.current_page} 
                lastPage={data.last_page} 
                onPageChange={handlePageChange}
                loading={loading}
            />
        )}
      </div>
    </Container>
  );
}

const root = document.getElementById('admin-audit-logs-root');
if (root) {
  createRoot(root).render(<AdminAuditLogsPage />);
}
