
import React from "react";
import { Link } from "react-router-dom";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { 
  FileText, 
  LayoutDashboard, 
  FileChartPie, 
  FileChartColumn, 
  MessageSquare, 
  Users, 
  Settings 
} from "lucide-react";
import MainLayout from "../components/layout/MainLayout";

export default function Index() {
  return (
    <MainLayout>
      <div className="container mx-auto my-8">
        <h1 className="text-3xl font-bold mb-6">Income ERP Dashboard</h1>
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <FileChartPie className="h-5 w-5" />
                Income Summary
              </CardTitle>
              <CardDescription>
                View and analyze income from various sources
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Link to="/income-summary">
                <Button className="w-full">Access Income Summary</Button>
              </Link>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <FileChartColumn className="h-5 w-5" />
                Power Consumption
              </CardTitle>
              <CardDescription>
                Monitor and analyze power usage statistics
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Link to="/power-consumption">
                <Button className="w-full">Access Power Consumption</Button>
              </Link>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <FileText className="h-5 w-5" />
                Monthly Performance Report
              </CardTitle>
              <CardDescription>
                Analyze income collection summary and performance data
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Link to="/mpr">
                <Button className="w-full">Access MPR</Button>
              </Link>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <MessageSquare className="h-5 w-5" />
                Remittances
              </CardTitle>
              <CardDescription>
                Manage and track all money remittances
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Link to="/power-consumption">
                <Button className="w-full" variant="outline">View Remittances</Button>
              </Link>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <LayoutDashboard className="h-5 w-5" />
                Transactions
              </CardTitle>
              <CardDescription>
                View and manage all financial transactions
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Link to="/power-consumption">
                <Button className="w-full" variant="outline">View Transactions</Button>
              </Link>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Users className="h-5 w-5" />
                User Management
              </CardTitle>
              <CardDescription>
                Manage system users and permissions
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Link to="/power-consumption">
                <Button className="w-full" variant="outline">Manage Users</Button>
              </Link>
            </CardContent>
          </Card>
        </div>
        
        <div className="mt-8">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Settings className="h-5 w-5" />
                System Overview
              </CardTitle>
              <CardDescription>
                System statistics and performance metrics
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
                  <p className="text-blue-600 text-sm font-medium">Today's Collection</p>
                  <h3 className="text-xl font-bold">₦ 253,480</h3>
                  <p className="text-sm text-gray-600">32 transactions</p>
                </div>
                
                <div className="bg-green-50 p-4 rounded-lg border border-green-200">
                  <p className="text-green-600 text-sm font-medium">This Week</p>
                  <h3 className="text-xl font-bold">₦ 1,245,890</h3>
                  <p className="text-sm text-gray-600">145 transactions</p>
                </div>
                
                <div className="bg-purple-50 p-4 rounded-lg border border-purple-200">
                  <p className="text-purple-600 text-sm font-medium">This Month</p>
                  <h3 className="text-xl font-bold">₦ 4,587,120</h3>
                  <p className="text-sm text-gray-600">412 transactions</p>
                </div>
                
                <div className="bg-amber-50 p-4 rounded-lg border border-amber-200">
                  <p className="text-amber-600 text-sm font-medium">Pending Actions</p>
                  <h3 className="text-xl font-bold">24</h3>
                  <p className="text-sm text-gray-600">Waiting for approval</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </MainLayout>
  );
}
