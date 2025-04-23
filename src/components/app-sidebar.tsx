
import React from "react";
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/ui/sidebar";
import { ClipboardCheck, FileCheck, CircleDollarSign, AlertTriangle, ArrowUpRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useAuth } from "@/contexts/AuthContext";

const menu = [
  { title: "Dashboard", icon: ClipboardCheck, url: "#" },
  { title: "Verified", icon: FileCheck, url: "#" },
  { title: "Revenue", icon: CircleDollarSign, url: "#" },
  { title: "Issues", icon: AlertTriangle, url: "#" },
];

export function AppSidebar() {
  const { logout } = useAuth();

  return (
    <Sidebar className="!bg-[#1eaedb] text-white flex flex-col">
      <SidebarHeader className="py-6 px-4">
        <span className="text-xl font-bold flex items-center gap-2">
          <CircleDollarSign className="h-6 w-6" />
          Audit
        </span>
      </SidebarHeader>
      <SidebarContent className="flex-1">
        <SidebarGroup>
          <SidebarGroupLabel className="text-white/80">Menu</SidebarGroupLabel>
          <SidebarGroupContent>
            <SidebarMenu>
              {menu.map((item) => (
                <SidebarMenuItem key={item.title}>
                  <SidebarMenuButton asChild className="text-white hover:bg-white/20">
                    <a href={item.url} className="flex items-center gap-2">
                      <item.icon />
                      <span>{item.title}</span>
                    </a>
                  </SidebarMenuButton>
                </SidebarMenuItem>
              ))}
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>
      </SidebarContent>
      <SidebarFooter className="px-4 pb-6 mt-auto">
        <Button
          variant="destructive"
          className="w-full"
          onClick={logout}
        >
          Logout
        </Button>
      </SidebarFooter>
    </Sidebar>
  );
}
