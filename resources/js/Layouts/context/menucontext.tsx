import React, { useState, createContext } from "react";

type MenuContextType = {
    activeMenu: string;
    setActiveMenu: React.Dispatch<React.SetStateAction<string>>;
};

export const MenuContext = createContext<MenuContextType | null>(null);

export const MenuProvider = ({ children }: { children: React.ReactNode }) => {
    const [activeMenu, setActiveMenu] = useState("");

    const value = {
        activeMenu,
        setActiveMenu,
    };

    return (
        <MenuContext.Provider value={value}>{children}</MenuContext.Provider>
    );
};
