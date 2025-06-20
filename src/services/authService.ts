
const API_BASE_URL = '/api';

export interface User {
  id: number;
  user_level: string;
  full_name: string;
  email: string;
  has_roles: string;
  status: string;
}

export interface Staff {
  id: number;
  user_id: number;
  department: string;
  full_name: string;
  phone_no: string;
  email: string;
  present_grade: string;
  level: string;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface LoginResponse {
  success: boolean;
  user?: User;
  staff?: Staff;
  message?: string;
  role?: string;
}

export const authService = {
  async login(credentials: LoginRequest): Promise<LoginResponse> {
    try {
      const response = await fetch(`${API_BASE_URL}/auth/login.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(credentials),
      });
      
      const data = await response.json();
      
      if (data.success) {
        // Store user data in localStorage
        localStorage.setItem('user', JSON.stringify(data.user));
        localStorage.setItem('staff', JSON.stringify(data.staff));
        localStorage.setItem('userRole', data.role);
      }
      
      return data;
    } catch (error) {
      console.error('Login error:', error);
      return { success: false, message: 'Network error occurred' };
    }
  },

  async logout(): Promise<void> {
    try {
      await fetch(`${API_BASE_URL}/auth/logout.php`, {
        method: 'POST',
      });
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      localStorage.removeItem('user');
      localStorage.removeItem('staff');
      localStorage.removeItem('userRole');
    }
  },

  getCurrentUser(): User | null {
    const userData = localStorage.getItem('user');
    return userData ? JSON.parse(userData) : null;
  },

  getCurrentStaff(): Staff | null {
    const staffData = localStorage.getItem('staff');
    return staffData ? JSON.parse(staffData) : null;
  },

  getUserRole(): string | null {
    return localStorage.getItem('userRole');
  },

  isAuthenticated(): boolean {
    return this.getCurrentUser() !== null;
  },

  hasPermission(requiredRoles: string[]): boolean {
    const userRole = this.getUserRole();
    if (!userRole) return false;
    
    // IT/E-Business has all access
    if (userRole === 'admin' || userRole === 'it_officer') return true;
    
    return requiredRoles.includes(userRole);
  }
};
