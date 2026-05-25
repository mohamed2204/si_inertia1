import { classNames } from 'primereact/utils';
import React, { forwardRef, useContext, useImperativeHandle, useRef } from 'react';
import { LayoutContext } from './context/layoutcontext';
import { Link, router, usePage } from '@inertiajs/react';
import { Avatar } from 'primereact/avatar'; // Import de l'Avatar

const AppTopbar = forwardRef((props, ref) => {
    const { layoutConfig, layoutState, onMenuToggle, showProfileSidebar } = useContext(LayoutContext);
    const { auth } = usePage().props; // Récupération des infos utilisateur

    const menubuttonRef = useRef(null);
    const topbarmenuRef = useRef(null);
    const topbarmenubuttonRef = useRef(null);

    useImperativeHandle(ref, () => ({
        menubutton: menubuttonRef.current,
        topbarmenu: topbarmenuRef.current,
        topbarmenubutton: topbarmenubuttonRef.current
    }));

    const logout = () => {
        router.post('/logout');
    };

    return (
        <div className="layout-topbar">
            <Link href="/" className="layout-topbar-logo">
                <img src={`/layout/images/logo-${layoutConfig.colorScheme !== 'light' ? 'white' : 'dark'}.svg`} width="47.22px" height={'35px'} alt="logo" />
                <span>SI</span>
            </Link>

            {/* Bouton Menu Principal (Hamburger) */}
            <button ref={menubuttonRef} type="button" className="p-link layout-menu-button layout-topbar-button" onClick={onMenuToggle}>
                <i className="pi pi-bars" />
            </button>
            {/* Bouton Menu Profil (les 3 points ou l'icône user sur mobile) */}
            <button ref={topbarmenubuttonRef} type="button" className="p-link layout-topbar-menu-button layout-topbar-button" onClick={showProfileSidebar}>
                <i className="pi pi-ellipsis-v" />
            </button>
            {/* Le menu de profil lui-même */}
            <div ref={topbarmenuRef} className={classNames('layout-topbar-menu', { 'layout-topbar-menu-mobile-active': layoutState.profileSidebarVisible })}>

                {/* --- SECTION INFO UTILISATEUR --- */}
                {auth.user && (
                    <div className="flex align-items-center px-3 gap-2 border-right-1 surface-border mr-2">
                        <Avatar
                            label={auth.user.name.charAt(0).toUpperCase()}
                            shape="circle"
                            style={{ backgroundColor: 'var(--primary-color)', color: '#ffffff' }}
                        />
                        <div className="flex flex-column">
                            <span className="font-bold text-900 line-height-1">{auth.user.name}</span>
                            <small className="text-600 line-height-1">{auth.user.roles?.[0] || 'Utilisateur'}</small>
                        </div>
                    </div>
                )}

                <button type="button" className="p-link layout-topbar-button">
                    <i className="pi pi-calendar"></i>
                    <span>Calendar</span>
                </button>

                <Link href="/profile" className="p-link layout-topbar-button">
                    <i className="pi pi-user"></i>
                    <span>Profile</span>
                </Link>

                <button type="button" className="p-link layout-topbar-button" onClick={logout}>
                    <i className="pi pi-sign-out" style={{ color: 'var(--red-500)' }}></i>
                    <span>Logout</span>
                </button>
            </div>
        </div>
    );
});

AppTopbar.displayName = 'AppTopbar';

export default AppTopbar;