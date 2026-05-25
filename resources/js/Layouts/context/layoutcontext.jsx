//'use client';
import React, { useState, createContext } from 'react';
//import { LayoutState, ChildContainerProps, LayoutConfig, LayoutContextProps } from '@/types';
export const LayoutContext = createContext({});

export const LayoutProvider = ({ children }) => {
    const [layoutConfig, setLayoutConfig] = useState({
        ripple: false,
        inputStyle: 'outlined',
        menuMode: 'static',
        colorScheme: 'light',
        theme: 'lara-light-indigo',
        scale: 14
    });

    const [layoutState, setLayoutState] = useState({

        staticMenuDesktopInactive: false,
        overlayMenuActive: false,
        profileSidebarVisible: false,
        configSidebarVisible: false,
        staticMenuMobileActive: false,
        menuHoverActive: false
    });

    // const onMenuToggle = () => {
    //     if (isOverlay()) {
    //         setLayoutState((prevLayoutState) => ({ ...prevLayoutState, overlayMenuActive: !prevLayoutState.overlayMenuActive }));
    //     }

    //     if (isDesktop()) {
    //         setLayoutState((prevLayoutState) => ({ ...prevLayoutState, staticMenuDesktopInactive: !prevLayoutState.staticMenuDesktopInactive }));
    //     } else {
    //         setLayoutState((prevLayoutState) => ({ ...prevLayoutState, staticMenuMobileActive: !prevLayoutState.staticMenuMobileActive }));
    //     }
    // };

    // const onMenuToggle = () => {
    //     if (isOverlay()) {
    //         setLayoutState((prev) => ({
    //             ...prev,
    //             overlayMenuActive: !prev.overlayMenuActive
    //         }));
    //     }

    //     if (isDesktop()) {
    //         setLayoutState((prev) => ({
    //             ...prev,
    //             staticMenuDesktopInactive: !prev.staticMenuDesktopInactive
    //         }));
    //     } else {
    //         // Mode Mobile : On bascule l'activation du menu mobile
    //         setLayoutState((prev) => ({
    //             ...prev,
    //             staticMenuMobileActive: !prev.staticMenuMobileActive
    //         }));
    //     }
    // };

    const onMenuToggle = () => {
        // On récupère la largeur actuelle au moment du clic
        const width = window.innerWidth;

        if (isOverlay()) {
            setLayoutState((prev) => ({
                ...prev,
                overlayMenuActive: !prev.overlayMenuActive
            }));
        }

        if (width > 991) {
            // Mode Desktop
            setLayoutState((prev) => ({
                ...prev,
                staticMenuDesktopInactive: !prev.staticMenuDesktopInactive
            }));
        } else {
            // Mode Mobile : On s'assure d'activer le menu mobile
            setLayoutState((prev) => ({
                ...prev,
                staticMenuMobileActive: !prev.staticMenuMobileActive
            }));
        }
    };
    const showProfileSidebar = () => {
        setLayoutState((prevLayoutState) => ({ ...prevLayoutState, profileSidebarVisible: !prevLayoutState.profileSidebarVisible }));
    };

    const isOverlay = () => {
        return layoutConfig.menuMode === 'overlay';
    };

    const isDesktop = () => {
        return window.innerWidth > 991;
    };

    const value = {
        layoutConfig,
        setLayoutConfig,
        layoutState,
        setLayoutState,
        onMenuToggle,
        showProfileSidebar
    };

    return <LayoutContext.Provider value={value}>{children}</LayoutContext.Provider>;
};
