
import React from "react";
import { Link } from "react-router-dom";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { FileText, Search, FileChartColumn, FileChartPie } from "lucide-react";

export default function Index() {
  return (
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
      </div>
    </div>
  );
}
