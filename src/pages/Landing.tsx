
import React from 'react';
import { Link } from 'react-router-dom';
import { ChartLine, Users, Shield, TrendingUp, ArrowRight, Building2, Award, Target } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export default function Landing() {
  const features = [
    {
      icon: ChartLine,
      title: "Income Analytics",
      description: "Comprehensive income tracking and analysis with detailed reporting capabilities."
    },
    {
      icon: TrendingUp,
      title: "Monthly Progress Reports",
      description: "Track performance metrics and generate detailed monthly progress reports."
    },
    {
      icon: Shield,
      title: "Secure Data Management",
      description: "Enterprise-grade security ensuring your financial data is always protected."
    },
    {
      icon: Users,
      title: "Multi-Department Access",
      description: "Role-based access control for different departments and user levels."
    }
  ];

  const stats = [
    { label: "Active Users", value: "250+" },
    { label: "Departments", value: "12" },
    { label: "Reports Generated", value: "5,000+" },
    { label: "Uptime", value: "99.9%" }
  ];

  const teamMembers = [
    {
      name: "John Doe",
      position: "System Administrator",
      department: "IT/E-Business",
      image: "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80"
    },
    {
      name: "Sarah Johnson",
      position: "Accounting Officer",
      department: "Accounts",
      image: "https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80"
    },
    {
      name: "Michael Chen",
      position: "Audit Inspector",
      department: "Audit/Inspections",
      image: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80"
    },
    {
      name: "Emily Davis",
      position: "Leasing Officer",
      department: "Leasing",
      image: "https://images.unsplash.com/photo-1438761681033-6461ffad8d80?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80"
    }
  ];

  return (
    <div className="min-h-screen bg-white">
      {/* Hero Section */}
      <section 
        className="relative min-h-screen bg-cover bg-center bg-fixed flex items-center justify-center"
        style={{
          backgroundImage: 'url(https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80)',
        }}
      >
        {/* Overlay */}
        <div className="absolute inset-0 bg-gradient-to-br from-blue-900/90 to-indigo-900/90"></div>
        
        {/* Content */}
        <div className="relative z-10 text-center text-white px-4 max-w-4xl mx-auto">
          <div className="flex justify-center items-center gap-3 mb-6">
            <ChartLine className="h-16 w-16 text-blue-300" />
            <h1 className="text-5xl md:text-7xl font-bold">Income ERP</h1>
          </div>
          
          <p className="text-xl md:text-2xl mb-8 text-blue-100">
            Professional Enterprise Resource Planning System for Income Management
          </p>
          
          <p className="text-lg mb-12 text-blue-200 max-w-2xl mx-auto">
            Streamline your organization's income tracking, reporting, and analysis with our comprehensive ERP solution designed for modern businesses.
          </p>
          
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Button asChild size="lg" className="bg-blue-600 hover:bg-blue-700 text-lg px-8 py-4">
              <Link to="/login">
                Access System
                <ArrowRight className="ml-2 h-5 w-5" />
              </Link>
            </Button>
            <Button variant="outline" size="lg" className="text-white border-white hover:bg-white hover:text-blue-900 text-lg px-8 py-4">
              Learn More
            </Button>
          </div>
        </div>
      </section>

      {/* Stats Section */}
      <section className="py-16 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8">
            {stats.map((stat, index) => (
              <div key={index} className="text-center">
                <div className="text-3xl md:text-4xl font-bold text-blue-600 mb-2">{stat.value}</div>
                <div className="text-gray-600">{stat.label}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-4">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 mb-4">Powerful Features</h2>
            <p className="text-xl text-gray-600 max-w-2xl mx-auto">
              Everything you need to manage your organization's income and financial operations efficiently
            </p>
          </div>
          
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            {features.map((feature, index) => (
              <Card key={index} className="text-center hover:shadow-lg transition-shadow">
                <CardHeader>
                  <div className="flex justify-center mb-4">
                    <feature.icon className="h-12 w-12 text-blue-600" />
                  </div>
                  <CardTitle className="text-xl">{feature.title}</CardTitle>
                </CardHeader>
                <CardContent>
                  <CardDescription className="text-gray-600">
                    {feature.description}
                  </CardDescription>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </section>

      {/* About Section */}
      <section className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4">
          <div className="grid lg:grid-cols-2 gap-16 items-center">
            <div>
              <h2 className="text-4xl font-bold text-gray-900 mb-6">About Our Organization</h2>
              <p className="text-lg text-gray-600 mb-6">
                We are a forward-thinking organization committed to excellence in financial management and operational efficiency. Our Income ERP system represents years of development and refinement to meet the unique needs of modern businesses.
              </p>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="flex items-center gap-3">
                  <Building2 className="h-8 w-8 text-blue-600" />
                  <div>
                    <div className="font-semibold">Established</div>
                    <div className="text-gray-600">2020</div>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <Award className="h-8 w-8 text-blue-600" />
                  <div>
                    <div className="font-semibold">Excellence</div>
                    <div className="text-gray-600">Award Winner</div>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <Target className="h-8 w-8 text-blue-600" />
                  <div>
                    <div className="font-semibold">Mission</div>
                    <div className="text-gray-600">Innovation</div>
                  </div>
                </div>
              </div>
            </div>
            <div className="relative">
              <img
                src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                alt="Office Building"
                className="rounded-lg shadow-2xl"
              />
            </div>
          </div>
        </div>
      </section>

      {/* Team Section */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-4">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 mb-4">Meet Our Team</h2>
            <p className="text-xl text-gray-600 max-w-2xl mx-auto">
              Dedicated professionals working together to deliver excellence in financial management
            </p>
          </div>
          
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            {teamMembers.map((member, index) => (
              <Card key={index} className="text-center hover:shadow-lg transition-shadow">
                <CardHeader>
                  <div className="flex justify-center mb-4">
                    <img
                      src={member.image}
                      alt={member.name}
                      className="w-24 h-24 rounded-full object-cover border-4 border-blue-100"
                    />
                  </div>
                  <CardTitle className="text-xl">{member.name}</CardTitle>
                  <CardDescription className="text-blue-600 font-medium">
                    {member.position}
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <p className="text-gray-600">{member.department}</p>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-20 bg-blue-900 text-white">
        <div className="max-w-4xl mx-auto px-4 text-center">
          <h2 className="text-4xl font-bold mb-6">Ready to Get Started?</h2>
          <p className="text-xl mb-8 text-blue-200">
            Join hundreds of organizations already using our Income ERP system to streamline their operations.
          </p>
          <Button asChild size="lg" className="bg-white text-blue-900 hover:bg-blue-50 text-lg px-8 py-4">
            <Link to="/login">
              Access Your Dashboard
              <ArrowRight className="ml-2 h-5 w-5" />
            </Link>
          </Button>
        </div>
      </section>

      {/* Footer */}
      <footer className="py-12 bg-gray-900 text-white">
        <div className="max-w-7xl mx-auto px-4">
          <div className="grid md:grid-cols-3 gap-8">
            <div>
              <div className="flex items-center gap-2 mb-4">
                <ChartLine className="h-8 w-8 text-blue-400" />
                <span className="text-2xl font-bold">Income ERP</span>
              </div>
              <p className="text-gray-400">
                Professional enterprise resource planning system for modern organizations.
              </p>
            </div>
            <div>
              <h3 className="text-lg font-semibold mb-4">Quick Links</h3>
              <ul className="space-y-2 text-gray-400">
                <li><Link to="/login" className="hover:text-white">Login</Link></li>
                <li><a href="#features" className="hover:text-white">Features</a></li>
                <li><a href="#about" className="hover:text-white">About</a></li>
                <li><a href="#team" className="hover:text-white">Team</a></li>
              </ul>
            </div>
            <div>
              <h3 className="text-lg font-semibold mb-4">Contact</h3>
              <div className="text-gray-400 space-y-2">
                <p>Email: info@incomerp.com</p>
                <p>Phone: +1 (555) 123-4567</p>
                <p>Address: 123 Business St, City, State 12345</p>
              </div>
            </div>
          </div>
          <div className="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
            <p>&copy; 2024 Income ERP System. All rights reserved.</p>
          </div>
        </div>
      </footer>
    </div>
  );
}
