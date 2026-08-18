import { createContext, useContext, useEffect, useState, type ReactNode } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Bell, Loader2, Mail, User } from "lucide-react";
import { insertSubscriberSchema, type InsertSubscriber } from "@shared/schema";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { apiRequest } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";

interface SubscribeContextValue {
  open: () => void;
  isSubscribed: boolean;
}

const SUBSCRIBED_STORAGE_KEY = "zmc_subscribed";

function readSubscribed(): boolean {
  try {
    return localStorage.getItem(SUBSCRIBED_STORAGE_KEY) === "true";
  } catch {
    return false;
  }
}

function persistSubscribed(): void {
  try {
    localStorage.setItem(SUBSCRIBED_STORAGE_KEY, "true");
  } catch {
    // Storage may be unavailable (private mode, blocked cookies); ignore.
  }
}

const SubscribeContext = createContext<SubscribeContextValue | null>(null);

export function useSubscribe(): SubscribeContextValue {
  const ctx = useContext(SubscribeContext);
  if (!ctx) {
    throw new Error("useSubscribe must be used within a SubscribeProvider");
  }
  return ctx;
}

export function SubscribeProvider({ children }: { children: ReactNode }) {
  const [isOpen, setIsOpen] = useState(false);
  const [isSubscribed, setIsSubscribed] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    if (readSubscribed()) {
      setIsSubscribed(true);
    }
  }, []);

  const form = useForm<InsertSubscriber>({
    resolver: zodResolver(insertSubscriberSchema),
    defaultValues: { fullName: "", email: "" },
  });

  const isSubmitting = form.formState.isSubmitting;

  const onSubmit = async (values: InsertSubscriber) => {
    try {
      const res = await apiRequest("POST", "/api/subscribe", values);
      const data = await res.json();
      persistSubscribed();
      setIsSubscribed(true);
      toast({
        title: data.alreadySubscribed
          ? "You're already subscribed"
          : "Subscription confirmed",
        description: data.alreadySubscribed
          ? `${values.email} is already on our list. We'll keep you posted on new vacancies and updates.`
          : `Thank you! We'll send notifications about vacancies and updates to ${values.email}.`,
      });
      form.reset();
      setIsOpen(false);
    } catch (error) {
      toast({
        title: "Subscription failed",
        description:
          error instanceof Error
            ? error.message
            : "Something went wrong. Please try again.",
        variant: "destructive",
      });
    }
  };

  return (
    <SubscribeContext.Provider
      value={{
        open: () => {
          form.reset();
          setIsOpen(true);
        },
        isSubscribed,
      }}
    >
      {children}

      <Dialog open={isOpen} onOpenChange={setIsOpen}>
        <DialogContent className="sm:max-w-[460px] top-[3vh] translate-y-0 max-h-[94vh] overflow-y-auto" data-testid="dialog-subscribe">
          <DialogHeader>
            <div
              className="w-12 h-12 rounded-xl flex items-center justify-center mb-2"
              style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}
            >
              <Bell className="w-6 h-6" />
            </div>
            <DialogTitle data-testid="text-subscribe-title">
              Subscribe for Free Updates
            </DialogTitle>
            <DialogDescription data-testid="text-subscribe-description">
              Enter your details to receive free notifications about new
              vacancies and updates straight to your email.
            </DialogDescription>
          </DialogHeader>

          <Form {...form}>
            <form
              onSubmit={form.handleSubmit(onSubmit)}
              className="space-y-4"
            >
              <FormField
                control={form.control}
                name="fullName"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Full Name</FormLabel>
                    <FormControl>
                      <div className="relative">
                        <User className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                        <Input
                          {...field}
                          placeholder="Your full name"
                          className="pl-9"
                          data-testid="input-subscribe-fullname"
                        />
                      </div>
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="email"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Email Address</FormLabel>
                    <FormControl>
                      <div className="relative">
                        <Mail className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                        <Input
                          {...field}
                          type="email"
                          placeholder="you@example.com"
                          className="pl-9"
                          data-testid="input-subscribe-email"
                        />
                      </div>
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <div
                className="flex items-start gap-2 rounded-xl p-3 text-[0.8rem] leading-relaxed"
                style={{ background: "var(--primary-soft)", color: "var(--neutral-600)" }}
                data-testid="text-subscribe-notice"
              >
                <Bell className="w-4 h-4 mt-0.5 flex-shrink-0" style={{ color: "var(--primary)" }} />
                <span>
                  By subscribing, you agree to receive notifications about any
                  updates and vacancies at the email address you provide. It's
                  free and you can opt out at any time.
                </span>
              </div>

              <button
                type="submit"
                disabled={isSubmitting}
                className="w-full py-3 rounded-xl font-semibold text-base border-none cursor-pointer text-white flex items-center justify-center gap-2 disabled:opacity-70"
                style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                data-testid="button-subscribe-submit"
              >
                {isSubmitting ? (
                  <>
                    <Loader2 className="w-5 h-5 animate-spin" />
                    Subscribing...
                  </>
                ) : (
                  <>
                    <Bell className="w-5 h-5" />
                    Subscribe for Free
                  </>
                )}
              </button>
            </form>
          </Form>
        </DialogContent>
      </Dialog>
    </SubscribeContext.Provider>
  );
}
