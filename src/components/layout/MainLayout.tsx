
import { ReactNode } from "react";
import { SidebarProvider, SidebarTrigger } from "@/components/ui/sidebar";
import { AppSidebar } from "@/components/app-sidebar";
import { useAuth } from "../../contexts/AuthContext";

interface MainLayoutProps {
  children: ReactNode;
}

export default function MainLayout({ children }: MainLayoutProps) {
  const { user } = useAuth();

  return (
    <div 
      className="min-h-screen bg-cover bg-center bg-fixed relative"
      style={{
        backgroundImage: 'url(https://images.unsplash.com/photo-1498050108023-c5249f4df085?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80)',
      }}
    >
      {/* Overlay for faint effect */}
      <div className="absolute inset-0 bg-white/90 backdrop-blur-[1px]"></div>
      
      {/* Content */}
      <div className="relative z-10">
        <SidebarProvider>
          <AppSidebar />
          <main className="flex-1">
            <div className="flex items-center gap-2 p-4 border-b bg-white/80 backdrop-blur-sm">
              <SidebarTrigger />
              <div className="flex-1" />
              {user && (
                <div className="text-sm text-gray-600">
                  Welcome, {user.full_name}
                </div>
              )}
            </div>
            <div className="p-6">
              {children}
            </div>
          </main>
        </SidebarProvider>
      </div>
    </div>
  );
}
