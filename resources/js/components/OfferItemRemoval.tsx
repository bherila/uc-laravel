import React from 'react';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";
import { Trash2, Loader2 } from 'lucide-react';

interface OfferItemRemovalProps {
  variantId: string;
  allocatedCount: number;
  isDeleting: boolean;
  onDelete: (variantId: string) => Promise<void>;
}

export const OfferItemRemoval: React.FC<OfferItemRemovalProps> = ({
  variantId,
  allocatedCount,
  isDeleting,
  onDelete,
}) => {
  return (
    <AlertDialog>
      <AlertDialogTrigger asChild>
        <Button
          variant="destructive"
          size="sm"
          disabled={isDeleting}
          title="Delete Product"
        >
          {isDeleting ? (
            <Loader2 className="h-4 w-4 animate-spin" />
          ) : (
            <Trash2 className="h-4 w-4" />
          )}
        </Button>
      </AlertDialogTrigger>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Are you sure?</AlertDialogTitle>
          <AlertDialogDescription className="space-y-3">
            <p>
              This will remove this product from the offer. 
              Only unallocated manifests will be deleted.
            </p>
            {allocatedCount > 0 && (
              <div className="bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded-md border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200">
                <p className="font-semibold mb-1">Warning: {allocatedCount} manifests are already allocated!</p>
                <p className="text-xs">
                  Allocated manifests cannot be deleted. If you need to delete them, 
                  you must first <strong>Cancel the order in Shopify</strong> to 
                  release the manifests back to the offer.
                </p>
              </div>
            )}
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction 
            onClick={() => onDelete(variantId)}
            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
          >
            Delete Product
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
};
