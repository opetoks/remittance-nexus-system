
import React, { useState, useEffect } from "react";
import { useToast } from "@/hooks/use-toast";
import { 
  Card, 
  CardContent, 
  CardHeader,
  CardTitle,
  CardDescription 
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { 
  Table, 
  TableBody, 
  TableCaption, 
  TableCell, 
  TableHead, 
  TableHeader, 
  TableRow 
} from "@/components/ui/table";
import { 
  Select, 
  SelectContent, 
  SelectItem, 
  SelectTrigger, 
  SelectValue 
} from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { FileText, Download } from "lucide-react";
import { useNavigate } from "react-router-dom";

interface IncomeData {
  incomeLine: string;
  days: { [key: number]: number };
  total: number;
}

// Dummy data to simulate a real API response
const DUMMY_DATA: IncomeData[] = [
  {
    incomeLine: "Abbatoir",
    days: {1: 50700, 2: 57500, 3: 51600, 4: 60200, 5: 74900, 6: 0, 7: 66400, 8: 66800, 9: 62100, 10: 58700, 11: 66400, 12: 74500, 13: 0, 14: 68700},
    total: 758500
  },
  {
    incomeLine: "Advert",
    days: {1: 0, 2: 0, 3: 0, 4: 0, 5: 0, 6: 0, 7: 0, 8: 1970000, 9: 0, 10: 1700000, 11: 0, 12: 0, 13: 0, 14: 450000},
    total: 4120000
  },
  {
    incomeLine: "Apple Loading - Offloading",
    days: {1: 9000, 2: 0, 3: 69000, 4: 8000, 5: 14000, 6: 0, 7: 30000, 8: 0, 9: 0, 10: 0, 11: 0, 12: 0, 13: 0, 14: 32000},
    total: 162000
  },
  {
    incomeLine: "Application Form",
    days: {1: 15000, 2: 12000, 3: 12000, 4: 16000, 5: 6000, 6: 0, 7: 15000, 8: 18000, 9: 24000, 10: 12000, 11: 18000, 12: 21000, 13: 0, 14: 12000},
    total: 181000
  },
  {
    incomeLine: "Bill Board",
    days: {1: 0, 2: 0, 3: 0, 4: 0, 5: 0, 6: 0, 7: 0, 8: 0, 9: 0, 10: 0, 11: 0, 12: 0, 13: 0, 14: 0},
    total: 0
  },
  {
    incomeLine: "Car Loading (taxi)",
    days: {1: 10000, 2: 8000, 3: 11000, 4: 9000, 5: 9000, 6: 0, 7: 10000, 8: 9000, 9: 0, 10: 0, 11: 0, 12: 0, 13: 0, 14: 17000},
    total: 83000
  },
  {
    incomeLine: "Car Park Ticket",
    days: {1: 898500, 2: 727300, 3: 878000, 4: 943000, 5: 1038600, 6: 0, 7: 886700, 8: 931700, 9: 854200, 10: 875000, 11: 871100, 12: 1037300, 13: 0, 14: 864000},
    total: 10805400
  }
];

const MPR: React.FC = () => {
  const [month, setMonth] = useState<string>("");
  const [year, setYear] = useState<string>("");
  const [incomeData, setIncomeData] = useState<IncomeData[]>(DUMMY_DATA);
  const [searchTerm, setSearchTerm] = useState<string>("");
  const [loading, setLoading] = useState<boolean>(false);
  const { toast } = useToast();
  const navigate = useNavigate();
  
  // Get current date for display
  const today = new Date().toISOString().split('T')[0];
  const currentMonth = new Date().toLocaleString('default', { month: 'long' });
  const currentYear = new Date().getFullYear().toString();
  
  useEffect(() => {
    // Set default values
    setMonth(currentMonth);
    setYear(currentYear);
  }, [currentMonth, currentYear]);
  
  // Function to load data based on selected month and year
  const loadData = () => {
    if (!month || !year) {
      toast({
        title: "Error",
        description: "Please select both month and year",
        variant: "destructive",
      });
      return;
    }
    
    setLoading(true);
    
    // Simulating API call
    setTimeout(() => {
      // For now we just use the dummy data
      // In a real implementation, we would fetch from an endpoint
      setIncomeData(DUMMY_DATA);
      setLoading(false);
      
      toast({
        title: "Success",
        description: `Loaded data for ${month} ${year}`,
      });
    }, 1000);
  };
  
  // Filter data based on search term
  const filteredData = incomeData.filter(item =>
    item.incomeLine.toLowerCase().includes(searchTerm.toLowerCase())
  );
  
  // Calculate column totals
  const calculateColumnTotals = () => {
    const totals: { [key: number]: number } = {};
    const daysToShow = 14; // Show 14 days for now
    
    // Initialize with zeros
    for (let i = 1; i <= daysToShow; i++) {
      totals[i] = 0;
    }
    
    // Sum up values
    incomeData.forEach(item => {
      for (let i = 1; i <= daysToShow; i++) {
        totals[i] += item.days[i] || 0;
      }
    });
    
    return totals;
  };
  
  const columnTotals = calculateColumnTotals();
  const grandTotal = Object.values(columnTotals).reduce((sum, value) => sum + value, 0);
  
  // Function to determine if a day is Sunday (for styling)
  const isSunday = (day: number) => {
    return day === 6 || day === 13; // In our dummy data, days 6 and 13 are Sundays
  };
  
  // Function to format numbers with commas
  const formatNumber = (num: number) => {
    return num.toLocaleString();
  };
  
  return (
    <div className="container mx-auto py-6">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold">MPR | General Summary</h1>
      </div>
      
      <div className="flex flex-wrap gap-4 mb-4 items-end">
        <div className="w-full md:w-auto">
          <Select value={month} onValueChange={setMonth}>
            <SelectTrigger className="w-[180px]">
              <SelectValue placeholder="Select month..." />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="January">January</SelectItem>
              <SelectItem value="February">February</SelectItem>
              <SelectItem value="March">March</SelectItem>
              <SelectItem value="April">April</SelectItem>
              <SelectItem value="May">May</SelectItem>
              <SelectItem value="June">June</SelectItem>
              <SelectItem value="July">July</SelectItem>
              <SelectItem value="August">August</SelectItem>
              <SelectItem value="September">September</SelectItem>
              <SelectItem value="October">October</SelectItem>
              <SelectItem value="November">November</SelectItem>
              <SelectItem value="December">December</SelectItem>
            </SelectContent>
          </Select>
        </div>
        
        <div className="w-full md:w-auto">
          <Select value={year} onValueChange={setYear}>
            <SelectTrigger className="w-[180px]">
              <SelectValue placeholder="Select year..." />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="2023">2023</SelectItem>
              <SelectItem value="2024">2024</SelectItem>
              <SelectItem value="2025">2025</SelectItem>
            </SelectContent>
          </Select>
        </div>
        
        <Button 
          onClick={loadData} 
          variant="default" 
          className="bg-green-500 hover:bg-green-600"
          disabled={loading}
        >
          {loading ? "Loading..." : "Load"}
        </Button>
        
        <Button 
          variant="outline" 
          onClick={() => navigate('/officer-summary')}
          className="ml-2"
        >
          View Officer by Officer Summary
        </Button>
      </div>
      
      <Card className="mb-6">
        <CardHeader className="bg-red-100 text-red-800">
          <CardTitle className="flex items-center">
            <FileText className="mr-2" />
            {month ? `This Month: ${month} ${year}` : "Select a period"} Collection Summary as at {today}
          </CardTitle>
        </CardHeader>
      </Card>
      
      <div className="flex justify-between mb-4">
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => navigator.clipboard.writeText(JSON.stringify(incomeData))}>
            Copy
          </Button>
          <Button variant="outline">
            <Download className="mr-2 h-4 w-4" /> Excel
          </Button>
        </div>
        
        <div className="flex items-center gap-2">
          <span>Search:</span>
          <Input
            type="text"
            placeholder="Filter income lines..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="max-w-xs"
          />
        </div>
      </div>
      
      <div className="rounded-md border overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="sticky left-0 bg-white">Income Line</TableHead>
              
              {Array.from({length: 14}, (_, i) => i + 1).map(day => (
                <TableHead 
                  key={day} 
                  className={`text-right ${isSunday(day) ? 'bg-red-400 text-white' : ''}`}
                >
                  <div className="text-xs text-red-600">{isSunday(day) ? 'Sun' : 'Day'}</div>
                  {day.toString().padStart(2, '0')}
                </TableHead>
              ))}
              
              <TableHead className="text-right">Total</TableHead>
            </TableRow>
          </TableHeader>
          
          <TableBody>
            {filteredData.map((item, index) => (
              <TableRow key={index}>
                <TableCell className="font-medium sticky left-0 bg-white">
                  {item.incomeLine}
                </TableCell>
                
                {Array.from({length: 14}, (_, i) => i + 1).map(day => (
                  <TableCell 
                    key={day} 
                    className={`text-right ${isSunday(day) ? 'bg-red-50' : ''}`}
                  >
                    {formatNumber(item.days[day] || 0)}
                  </TableCell>
                ))}
                
                <TableCell className="text-right font-semibold">
                  {formatNumber(
                    Object.values(item.days).reduce((sum, value) => sum + value, 0)
                  )}
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
          
          <TableHeader>
            <TableRow>
              <TableHead className="sticky left-0 bg-gray-100"></TableHead>
              
              {Array.from({length: 14}, (_, i) => i + 1).map(day => (
                <TableHead 
                  key={day} 
                  className={`text-right ${isSunday(day) ? 'bg-red-200 text-red-800' : 'bg-gray-100'}`}
                >
                  {formatNumber(columnTotals[day] || 0)}
                </TableHead>
              ))}
              
              <TableHead className="text-right bg-gray-100">
                {formatNumber(grandTotal)}
              </TableHead>
            </TableRow>
          </TableHeader>
        </Table>
      </div>
    </div>
  );
};

export default MPR;
