
import React, { createContext, useContext, useState, useEffect } from "react";
import { authService, User, Staff } from "../services/authService";

interface AuthContextType {
  isAuthenticated: boolean;
  user: User | null;
  staff: Staff | null;
  userRole: string | null;
  login: (email: string, password: string) => Promise<boolean>;
  logout: () => void;
  hasPermission: (roles: string[]) => boolean;
  isLoading: boolean;
}

const AuthContext = createContext<AuthContextType>({
  isAuthenticated: false,
  user: null,
  staff: null,
  userRole: null,
  login: async () => false,
  logout: () => {},
  hasPermission: () => false,
  isLoading: true,
});

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [isAuthenticated, setIsAuthenticated] = useState<boolean>(false);
  const [user, setUser] = useState<User | null>(null);
  const [staff, setStaff] = useState<Staff | null>(null);
  const [userRole, setUserRole] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    const checkAuth = () => {
      const currentUser = authService.getCurrentUser();
      const currentStaff = authService.getCurrentStaff();
      const role = authService.getUserRole();
      
      if (currentUser && currentStaff) {
        setUser(currentUser);
        setStaff(currentStaff);
        setUserRole(role);
        setIsAuthenticated(true);
      }
      setIsLoading(false);
    };
    
    checkAuth();
  }, []);

  const login = async (email: string, password: string): Promise<boolean> => {
    try {
      const response = await authService.login({ email, password });
      
      if (response.success && response.user && response.staff) {
        setUser(response.user);
        setStaff(response.staff);
        setUserRole(response.role || null);
        setIsAuthenticated(true);
        return true;
      } else {
        console.error("Login failed:", response.message);
        return false;
      }
    } catch (error) {
      console.error("Login error:", error);
      return false;
    }
  };

  const logout = async () => {
    await authService.logout();
    setUser(null);
    setStaff(null);
    setUserRole(null);
    setIsAuthenticated(false);
  };

  const hasPermission = (roles: string[]): boolean => {
    return authService.hasPermission(roles);
  };

  return (
    <AuthContext.Provider value={{ 
      isAuthenticated, 
      user, 
      staff, 
      userRole, 
      login, 
      logout, 
      hasPermission,
      isLoading
    }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
