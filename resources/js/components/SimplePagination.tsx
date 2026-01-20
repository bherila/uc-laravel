import React from 'react';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

interface PaginationProps {
  currentPage: number;
  lastPage: number;
  onPageChange: (page: number) => void;
  loading?: boolean;
}

export function SimplePagination({ currentPage, lastPage, onPageChange, loading }: PaginationProps) {
  if (lastPage <= 1) return null;

  const pages = Array.from({ length: lastPage }, (_, i) => i + 1);

  return (
    <div className="flex items-center gap-2">
      <Button
        variant="outline"
        size="sm"
        onClick={() => onPageChange(currentPage - 1)}
        disabled={currentPage <= 1 || loading}
      >
        <ChevronLeft className="h-4 w-4" />
        Previous
      </Button>
      
      <Select 
        value={currentPage.toString()} 
        onValueChange={(val) => onPageChange(parseInt(val, 10))}
        disabled={!!loading}
      >
        <SelectTrigger className="h-8 w-[130px] text-xs">
          <SelectValue placeholder={`Page ${currentPage}`} />
        </SelectTrigger>
        <SelectContent>
          {pages.map((p) => (
            <SelectItem key={p} value={p.toString()}>
              Page {p} of {lastPage}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      <Button
        variant="outline"
        size="sm"
        onClick={() => onPageChange(currentPage + 1)}
        disabled={currentPage >= lastPage || loading}
      >
        Next
        <ChevronRight className="h-4 w-4" />
      </Button>
    </div>
  );
}
