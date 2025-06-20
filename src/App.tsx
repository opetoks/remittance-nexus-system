
import { BrowserRouter as Router, Routes, Route, Navigate } from "react-router-dom";
import { AuthProvider } from "./contexts/AuthContext";
import ProtectedRoute from "./components/ProtectedRoute";
import Login from "./pages/Login";
import Index from "./pages/Index";
import IncomeSummary from "./pages/IncomeSummary";
import PowerConsumption from "./pages/PowerConsumption";
import MPR from "./pages/MPR";
import NotFound from "./pages/NotFound";
import "./App.css";

function App() {
  return (
    <AuthProvider>
      <Router>
        <Routes>
          <Route path="/login" element={<Login />} />
          
          <Route 
            path="/" 
            element={
              <ProtectedRoute>
                <Index />
              </ProtectedRoute>
            } 
          />
          
          <Route 
            path="/income-summary" 
            element={
              <ProtectedRoute requiredRoles={['admin', 'it_officer', 'accounting_officer', 'auditor']}>
                <IncomeSummary />
              </ProtectedRoute>
            } 
          />
          
          <Route 
            path="/power-consumption" 
            element={
              <ProtectedRoute requiredRoles={['admin', 'it_officer', 'accounting_officer']}>
                <PowerConsumption />
              </ProtectedRoute>
            } 
          />
          
          <Route 
            path="/mpr" 
            element={
              <ProtectedRoute requiredRoles={['admin', 'it_officer', 'accounting_officer', 'auditor']}>
                <MPR />
              </ProtectedRoute>
            } 
          />
          
          <Route path="/404" element={<NotFound />} />
          <Route path="*" element={<Navigate to="/404" replace />} />
        </Routes>
      </Router>
    </AuthProvider>
  );
}

export default App;
