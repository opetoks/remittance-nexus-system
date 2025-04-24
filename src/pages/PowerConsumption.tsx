
import React, { useState, useEffect } from "react";
import { useForm } from "react-hook-form";
import { PowerConsumption, PowerConsumptionHistory } from "../types/powerConsumption";
import { getCustomerByShopNo, saveNewMeterReading, getConsumptionHistory } from "../services/powerConsumptionService";
import { 
  calculateConsumption, 
  calculateCost, 
  calculateVAT, 
  calculateTotalPayable,
  calculateBalance,
  formatCurrency,
  generateMonthYear
} from "../utils/powerCalculations";
import { useAuth } from "../contexts/AuthContext";
import MainLayout from "../components/layout/MainLayout";

// Import UI components
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { 
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage
} from "@/components/ui/form";

import { CircleDollarSign, FileText, Gauge } from "lucide-react";

const PowerConsumption: React.FC = () => {
  const { user } = useAuth();
  const [customer, setCustomer] = useState<PowerConsumption | null>(null);
  const [consumptionHistory, setConsumptionHistory] = useState<PowerConsumptionHistory[]>([]);
  const [calculationResults, setCalculationResults] = useState<{
    consumption: number;
    cost: number;
    vatAmount: number;
    totalCost: number;
    totalPayable: number;
    balance: number;
  } | null>(null);
  const [isCalculated, setIsCalculated] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  // Initialize form
  const form = useForm({
    defaultValues: {
      current_month: generateMonthYear(),
      shop_no: "",
      customer_name: "",
      previous_reading: 0,
      present_reading: 0,
      billing_category: "",
      date_of_reading: new Date().toISOString().split('T')[0],
    },
  });
  
  // Watch form fields for real-time calculations
  const shopNo = form.watch("shop_no");
  const previousReading = form.watch("previous_reading");
  const presentReading = form.watch("present_reading");

  // Fetch customer data when shop number changes
  useEffect(() => {
    if (shopNo && shopNo.trim() !== "") {
      fetchCustomerData(shopNo);
    } else {
      setCustomer(null);
      setConsumptionHistory([]);
      form.reset({
        ...form.getValues(),
        customer_name: "",
        previous_reading: 0,
        billing_category: ""
      });
    }
  }, [shopNo]);

  // Fetch customer data
  const fetchCustomerData = async (shopNo: string) => {
    setIsLoading(true);
    setError(null);
    try {
      const customerData = await getCustomerByShopNo(shopNo);
      if (customerData) {
        setCustomer(customerData);
        form.setValue("customer_name", customerData.customer_name);
        form.setValue("previous_reading", customerData.previous_reading);
        form.setValue("billing_category", customerData.billing_category);
        
        // Also fetch consumption history
        const history = await getConsumptionHistory(customerData.shop_id);
        setConsumptionHistory(history);
      } else {
        setError("No customer found with this shop number");
      }
    } catch (err) {
      setError("Error fetching customer data. Please try again.");
      console.error(err);
    } finally {
      setIsLoading(false);
    }
  };

  // Handle form submission
  const onSubmit = async (data: any) => {
    if (!customer) {
      setError("Customer data not found. Please enter a valid shop number.");
      return;
    }
    
    if (!isCalculated) {
      setError("Please calculate the bill before saving.");
      return;
    }

    setIsLoading(true);
    setError(null);
    setSuccess(null);
    
    try {
      if (!calculationResults) {
        throw new Error("Calculation results are missing");
      }
      
      // Prepare data for saving
      const readingData: PowerConsumption = {
        ...customer,
        current_month: data.current_month,
        present_reading: data.present_reading,
        date_of_reading: data.date_of_reading,
        consumption: calculationResults.consumption,
        cost: calculationResults.totalCost, // Total cost including VAT
        vat_on_cost: calculationResults.vatAmount,
        total_payable: calculationResults.totalPayable,
        balance: calculationResults.balance,
        staff_id: user?.id || "",
        staff_name: user?.name || "",
        updating_officer_id: user?.id || "",
        updating_officer_name: user?.name || ""
      };
      
      const response = await saveNewMeterReading(readingData);
      
      if (response.success) {
        setSuccess("Meter reading saved successfully!");
        form.reset();
        setIsCalculated(false);
        setCalculationResults(null);
        setCustomer(null);
        setConsumptionHistory([]);
      } else {
        setError(response.message || "Failed to save meter reading");
      }
    } catch (err: any) {
      setError(err.message || "An error occurred while saving. Please try again.");
      console.error(err);
    } finally {
      setIsLoading(false);
    }
  };

  // Calculate bill
  const calculateBill = () => {
    if (!customer) {
      setError("Customer data not found. Please enter a valid shop number.");
      return;
    }
    
    const present = form.getValues("present_reading");
    const previous = form.getValues("previous_reading");
    
    if (present <= previous) {
      setError("Present reading must be greater than previous reading");
      return;
    }
    
    const consumption = calculateConsumption(previous, present);
    const baseCost = calculateCost(consumption, customer.tariff);
    const vatAmount = calculateVAT(baseCost);
    const totalCost = baseCost + vatAmount;
    const totalPayable = calculateTotalPayable(baseCost, vatAmount, customer.previous_outstanding);
    const balance = calculateBalance(totalPayable, customer.total_paid);
    
    setCalculationResults({
      consumption,
      cost: baseCost,
      vatAmount,
      totalCost,
      totalPayable,
      balance
    });
    
    setIsCalculated(true);
    setError(null);
  };

  // Function to get last 6 records from history
  const getLastSixMonths = () => {
    return consumptionHistory.slice(0, 6);
  };

  return (
    <MainLayout>
      <div className="flex flex-col gap-6">
        <div className="flex items-center">
          <CircleDollarSign className="mr-2 h-6 w-6" />
          <h1 className="text-3xl font-bold">Power Consumption Management</h1>
        </div>
        
        <div className="grid md:grid-cols-3 gap-6">
          {/* Customer Form */}
          <div className="md:col-span-2">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center">
                  <Gauge className="mr-2 h-5 w-5" />
                  New Meter Reading
                </CardTitle>
                <CardDescription>
                  Enter the meter reading details for the current month
                </CardDescription>
              </CardHeader>
              <CardContent>
                <Form {...form}>
                  <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                      <FormField
                        control={form.control}
                        name="current_month"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>Billing Month</FormLabel>
                            <FormControl>
                              <Input {...field} readOnly />
                            </FormControl>
                            <FormDescription>Billing month is automatically set</FormDescription>
                          </FormItem>
                        )}
                      />
                      
                      <FormField
                        control={form.control}
                        name="shop_no"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>Shop Number</FormLabel>
                            <FormControl>
                              <Input {...field} placeholder="Enter shop number" />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                    </div>
                    
                    <FormField
                      control={form.control}
                      name="customer_name"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>Customer Name</FormLabel>
                          <FormControl>
                            <Input {...field} readOnly />
                          </FormControl>
                        </FormItem>
                      )}
                    />
                    
                    <div className="grid grid-cols-2 gap-4">
                      <FormField
                        control={form.control}
                        name="previous_reading"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>Previous Reading (kW)</FormLabel>
                            <FormControl>
                              <Input 
                                {...field} 
                                type="number"
                                step="0.01"
                                readOnly
                              />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                      
                      <FormField
                        control={form.control}
                        name="present_reading"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>Present Reading (kW)</FormLabel>
                            <FormControl>
                              <Input 
                                {...field} 
                                type="number"
                                step="0.01"
                                onChange={(e) => {
                                  field.onChange(parseFloat(e.target.value) || 0);
                                  setIsCalculated(false);
                                }}
                              />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                    </div>
                    
                    <div className="grid grid-cols-2 gap-4">
                      <FormField
                        control={form.control}
                        name="billing_category"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>Billing Category</FormLabel>
                            <FormControl>
                              <Input {...field} readOnly />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                      
                      <FormField
                        control={form.control}
                        name="date_of_reading"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>Date of Reading</FormLabel>
                            <FormControl>
                              <Input {...field} type="date" />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </div>

                    {error && (
                      <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                        {error}
                      </div>
                    )}
                    
                    {success && (
                      <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                        {success}
                      </div>
                    )}
                    
                    <div className="flex gap-2">
                      <Button 
                        type="button" 
                        onClick={calculateBill}
                        disabled={!customer || isLoading}
                      >
                        Calculate Bill
                      </Button>
                      
                      <Button 
                        type="submit" 
                        disabled={!isCalculated || isLoading}
                      >
                        {isLoading ? "Saving..." : "Save Reading"}
                      </Button>
                    </div>
                  </form>
                </Form>
              </CardContent>
            </Card>
            
            {/* Bill Summary */}
            {isCalculated && calculationResults && (
              <Card className="mt-4">
                <CardHeader>
                  <CardTitle className="flex items-center">
                    <FileText className="mr-2 h-5 w-5" />
                    Bill Summary
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                      <div className="space-y-1">
                        <p className="text-sm font-medium">Customer:</p>
                        <p className="text-lg font-bold">{customer?.customer_name}</p>
                      </div>
                      <div className="space-y-1">
                        <p className="text-sm font-medium">Shop No:</p>
                        <p className="text-lg font-bold">{customer?.shop_no}</p>
                      </div>
                    </div>
                    
                    <div className="grid grid-cols-2 gap-4">
                      <div className="space-y-1">
                        <p className="text-sm font-medium">Meter No:</p>
                        <p>{customer?.meter_no}</p>
                      </div>
                      <div className="space-y-1">
                        <p className="text-sm font-medium">Billing Month:</p>
                        <p>{form.getValues("current_month")}</p>
                      </div>
                    </div>
                    
                    <div className="border-t pt-4">
                      <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-1">
                          <p className="text-sm font-medium">Previous Reading:</p>
                          <p>{previousReading} kW</p>
                        </div>
                        <div className="space-y-1">
                          <p className="text-sm font-medium">Present Reading:</p>
                          <p>{presentReading} kW</p>
                        </div>
                      </div>
                      
                      <div className="mt-4 space-y-2">
                        <div className="flex justify-between">
                          <span>Consumption</span>
                          <span>{calculationResults.consumption.toFixed(2)} kW</span>
                        </div>
                        <div className="flex justify-between">
                          <span>Rate (Tariff)</span>
                          <span>{formatCurrency(customer?.tariff || 0)} per kW</span>
                        </div>
                        <div className="flex justify-between">
                          <span>Consumption Cost</span>
                          <span>{formatCurrency(calculationResults.cost)}</span>
                        </div>
                        <div className="flex justify-between">
                          <span>VAT (7.5%)</span>
                          <span>{formatCurrency(calculationResults.vatAmount)}</span>
                        </div>
                        <div className="flex justify-between">
                          <span>Previous Outstanding</span>
                          <span>{formatCurrency(customer?.previous_outstanding || 0)}</span>
                        </div>
                        <div className="flex justify-between font-bold">
                          <span>Total Payable</span>
                          <span>{formatCurrency(calculationResults.totalPayable)}</span>
                        </div>
                        <div className="flex justify-between">
                          <span>Amount Paid</span>
                          <span>{formatCurrency(customer?.total_paid || 0)}</span>
                        </div>
                        <div className="flex justify-between pt-2 border-t font-bold text-lg">
                          <span>Balance</span>
                          <span>{formatCurrency(calculationResults.balance)}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </CardContent>
                <CardFooter>
                  <Button variant="outline" className="w-full">
                    Print Bill
                  </Button>
                </CardFooter>
              </Card>
            )}
          </div>
          
          {/* Consumption History */}
          <div className="md:col-span-1">
            <Card>
              <CardHeader>
                <CardTitle>Consumption History</CardTitle>
                <CardDescription>Last 6 months consumption</CardDescription>
              </CardHeader>
              <CardContent>
                {consumptionHistory.length > 0 ? (
                  <div className="space-y-4">
                    {getLastSixMonths().map((history, index) => (
                      <div key={index} className="border-b pb-3">
                        <div className="flex justify-between font-medium">
                          <span>{history.current_month}</span>
                          <span>{history.consumption.toFixed(2)} kW</span>
                        </div>
                        <div className="text-sm text-gray-500">
                          {formatCurrency(history.cost)}
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="text-center py-8 text-gray-500">
                    {customer ? "No consumption history found" : "Enter a shop number to view history"}
                  </div>
                )}
              </CardContent>
            </Card>
            
            {customer && (
              <Card className="mt-4">
                <CardHeader>
                  <CardTitle>Customer Details</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-2">
                    <div className="grid grid-cols-2">
                      <span className="font-medium">Meter No:</span>
                      <span>{customer.meter_no}</span>
                    </div>
                    <div className="grid grid-cols-2">
                      <span className="font-medium">Model:</span>
                      <span>{customer.meter_model}</span>
                    </div>
                    <div className="grid grid-cols-2">
                      <span className="font-medium">Tariff:</span>
                      <span>{formatCurrency(customer.tariff)}</span>
                    </div>
                    <div className="grid grid-cols-2">
                      <span className="font-medium">Users:</span>
                      <span>{customer.no_of_users}</span>
                    </div>
                    <div className="grid grid-cols-2">
                      <span className="font-medium">Category:</span>
                      <span>{customer.billing_category}</span>
                    </div>
                  </div>
                </CardContent>
              </Card>
            )}
          </div>
        </div>
      </div>
    </MainLayout>
  );
};

export default PowerConsumption;
