
import React, { useState, useEffect } from "react";
import { Routes, Route } from "react-router-dom";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { ThemeProvider } from "./components/theme-provider";
import { Toaster } from "@/components/ui/toaster";

import Index from "./pages/Index";
import IncomeSummary from "./pages/IncomeSummary";
import PowerConsumption from "./pages/PowerConsumption";
import NotFound from "./pages/NotFound";
import MPR from "./pages/MPR";

const queryClient = new QueryClient();

const App = () => {
  const [isDarkTheme, setIsDarkTheme] = useState(false);

  useEffect(() => {
    // You can add logic here to check user preferences or system settings
    // to determine the initial theme.
    // For example, check local storage or use a media query.
    // For now, let's default to light theme.
  }, []);

  return (
    <QueryClientProvider client={queryClient}>
      <ThemeProvider defaultTheme="light" storageKey="vite-ui-theme">
        <Routes>
          <Route path="/" element={<Index />} />
          <Route path="/income-summary" element={<IncomeSummary />} />
          <Route path="/power-consumption" element={<PowerConsumption />} />
          <Route path="/mpr" element={<MPR />} />
          <Route path="*" element={<NotFound />} />
        </Routes>
        <Toaster />
      </ThemeProvider>
    </QueryClientProvider>
  );
};

export default App;
