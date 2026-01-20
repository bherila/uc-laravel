import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
  DialogFooter,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Copy, Check } from 'lucide-react';

interface AuditLogDetailCellProps {
  detail: string | null;
}

export const AuditLogDetailCell: React.FC<AuditLogDetailCellProps> = ({ detail }) => {
  const [copied, setCopied] = useState(false);

  if (!detail) return null;

  if (detail.length <= 1000) {
    return (
      <div className="max-w-lg break-words">
        {detail}
      </div>
    );
  }

  const sizeKb = (detail.length / 1024).toFixed(1);

  const handleCopy = () => {
    navigator.clipboard.writeText(detail);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <Dialog>
      <DialogTrigger asChild>
        <Button variant="link" className="p-0 h-auto font-normal text-blue-600 hover:text-blue-800">
          View Detail ({sizeKb} KB)
        </Button>
      </DialogTrigger>
      <DialogContent className="max-w-3xl max-h-[80vh] flex flex-col">
        <DialogHeader>
          <DialogTitle>Audit Log Detail</DialogTitle>
        </DialogHeader>
        <div className="flex-1 overflow-auto py-4">
          <Textarea 
            readOnly 
            value={detail} 
            className="h-full min-h-[400px] font-mono text-xs resize-none"
          />
        </div>
        <DialogFooter className="sm:justify-start">
          <Button type="button" variant="secondary" onClick={handleCopy}>
            {copied ? (
              <>
                <Check className="mr-2 h-4 w-4" />
                Copied
              </>
            ) : (
              <>
                <Copy className="mr-2 h-4 w-4" />
                Copy to Clipboard
              </>
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};
