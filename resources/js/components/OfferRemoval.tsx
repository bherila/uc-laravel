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
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/tooltip";

interface OfferRemovalProps {
  offerId: number;
  allocatedCount: number;
  isDeleting: boolean;
  onDelete: (id: number) => Promise<void>;
}

export function OfferRemoval({
  offerId,
  allocatedCount,
  isDeleting,
  onDelete,
}: OfferRemovalProps) {
  const isDisabled = isDeleting || allocatedCount > 0;

  const button = (
    <div className="inline-block">
      <Button
        variant="destructive"
        size="sm"
        disabled={isDisabled}
      >
        {isDeleting ? (
          <Loader2 className="h-4 w-4 animate-spin" />
        ) : (
          <Trash2 className="h-4 w-4" />
        )}
      </Button>
    </div>
  );

  return (
    <AlertDialog>
      <Tooltip>
        <TooltipTrigger asChild>
          {isDisabled ? (
            button
          ) : (
            <AlertDialogTrigger asChild>
              {button}
            </AlertDialogTrigger>
          )}
        </TooltipTrigger>
        <TooltipContent>
          {allocatedCount > 0 
            ? `Cannot delete offer with ${allocatedCount} allocated manifests` 
            : "Delete Offer"}
        </TooltipContent>
      </Tooltip>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Are you sure?</AlertDialogTitle>
          <AlertDialogDescription>
            This will permanently delete this offer and any unassigned manifests. 
            This action cannot be undone.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction 
            onClick={() => onDelete(offerId)}
            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
          >
            Delete Offer
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
