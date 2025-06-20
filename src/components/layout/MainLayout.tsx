
import React from "react";
import { Button } from "@/components/ui/button";
import { useAuth } from "../../contexts/AuthContext";
import { LogOut, User } from "lucide-react";

interface MainLayoutProps {
  children: React.ReactNode;
}

export default function MainLayout({ children }: MainLayoutProps) {
  const { user, staff, logout, userRole } = useAuth();

  const handleLogout = async () => {
    await logout();
  };

  const getDepartmentLabel = (department: string | undefined) => {
    if (!department) return 'Unknown Department';
    
    switch (department.toLowerCase()) {
      case 'it/e-business':
        return 'IT/E-Business (Admin)';
      case 'accounts':
        return 'Accounts Department';
      case 'wealth creation':
        return 'Wealth Creation';
      case 'audit/inspections':
        return 'Audit & Inspections';
      case 'leasing':
        return 'Leasing Department';
      default:
        return department;
    }
  };

  const getRoleColor = (role: string | null) => {
    if (!role) return 'bg-gray-100 text-gray-800';
    
    switch (role) {
      case 'admin':
      case 'it_officer':
        return 'bg-purple-100 text-purple-800';
      case 'accounting_officer':
        return 'bg-blue-100 text-blue-800';
      case 'wealth_creation':
        return 'bg-green-100 text-green-800';
      case 'auditor':
        return 'bg-orange-100 text-orange-800';
      case 'leasing_officer':
        return 'bg-indigo-100 text-indigo-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center">
              <h1 className="text-xl font-semibold text-gray-900">Income ERP System</h1>
            </div>
            
            {user && staff && (
              <div className="flex items-center space-x-4">
                <div className="text-right">
                  <div className="flex items-center space-x-2">
                    <User className="h-4 w-4 text-gray-500" />
                    <span className="text-sm font-medium text-gray-900">
                      {staff.full_name}
                    </span>
                  </div>
                  <div className="flex items-center space-x-2 mt-1">
                    <span className="text-xs text-gray-500">
                      {getDepartmentLabel(staff.department)}
                    </span>
                    <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${getRoleColor(userRole)}`}>
                      {userRole?.replace('_', ' ').toUpperCase()}
                    </span>
                  </div>
                </div>
                
                <Button
                  variant="outline"
                  size="sm"
                  onClick={handleLogout}
                  className="flex items-center space-x-1"
                >
                  <LogOut className="h-4 w-4" />
                  <span>Logout</span>
                </Button>
              </div>
            )}
          </div>
        </div>
      </header>

      {/* Main content */}
      <main className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        {children}
      </main>
    </div>
  );
}
