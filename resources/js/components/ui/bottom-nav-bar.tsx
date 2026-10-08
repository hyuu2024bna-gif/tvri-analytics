"use client";

import { useState } from "react";

import { motion } from "framer-motion";
import {
  Home,
  LineChart,
  CreditCard,
  MessageCircle,
  Trophy,
  User,
} from "lucide-react";

import { cn } from "@/lib/utils";

const navItems = [
  { label: "Home", icon: Home },
  { label: "Portfolio", icon: LineChart },
  { label: "Card", icon: CreditCard },
  { label: "Activity", icon: MessageCircle },
  { label: "Rewards", icon: Trophy },
  { label: "Profile", icon: User },
];

export function BottomNavBar() {
  const [activeTab, setActiveTab] = useState("Home");

  return (
    <div className="fixed bottom-0 left-0 right-0 z-50 flex justify-center p-4">
      <nav className="flex items-center gap-1 rounded-full border bg-background/80 p-2 shadow-lg backdrop-blur-lg">
        {navItems.map((item) => {
          const Icon = item.icon;
          const isActive = activeTab === item.label;

          return (
            <button
              key={item.label}
              onClick={() => setActiveTab(item.label)}
              className={cn(
                "relative flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition-colors",
                isActive
                  ? "text-primary-foreground"
                  : "text-muted-foreground hover:text-foreground",
              )}
            >
              {isActive && (
                <motion.div
                  layoutId="active-pill"
                  className="absolute inset-0 rounded-full bg-primary"
                  transition={{ type: "spring", stiffness: 300, damping: 30 }}
                />
              )}
              <Icon className="relative z-10 h-5 w-5" />
              {isActive && (
                <span className="relative z-10">{item.label}</span>
              )}
            </button>
          );
        })}
      </nav>
    </div>
  );
}
