/* eslint-disable @next/next/no-img-element */
import React, { useContext, useMemo } from 'react';
import AppMenuitem from './AppMenuitem';
import { LayoutContext } from './context/layoutcontext';
import { MenuProvider } from './context/menucontext';
import { usePage } from '@inertiajs/react';

const AppMenu = () => {
    const { layoutConfig } = useContext(LayoutContext);
    const { auth } = usePage().props;
    const user = auth?.user;
    console.log('Données utilisateur dans AppMenu:', user); // Debug pour vérifier les données de l'utilisateur

    /**
     * Helper unique et dynamique pour tester l'accès à un module
     */
    const hasModuleAccess = (moduleName) => {
        if (!user) return false;

        // Passe-droit complet pour l'administrateur
        if (user.is_admin || user.allowed_modules?.includes('all')) return true;

        // Vérifie si le module demandé est dans la liste Laravel
        return user.allowed_modules?.includes(moduleName);
    };

    // /**
    //  * 1. Sécurisation stricte des helpers (Retournent TOUJOURS true ou false)
    //  */
    // const hasRole = (roleName) => {
    //     if (!user) return false;
    //     if (user.is_admin === true || user.is_admin === 1) return true;

    //     if (user.roles && Array.isArray(user.roles)) {
    //         if (user.roles.includes(roleName)) return true;
    //     }

    //     if (user.groups && Array.isArray(user.groups)) {
    //         if (user.groups.some(g => g.code === roleName || g.name === roleName)) return true;
    //     }

    //     return false; // Force false au lieu de undefined
    // };

    // const hasPermission = (perm) => {
    //     if (!user) return false;
    //     if (user.is_admin === true || user.is_admin === 1) return true;

    //     if (user.groups?.some(g => g.name === 'Direction / Administration' || g.code === 'admin')) {
    //         return true;
    //     }

    //     if (user.permissions && Array.isArray(user.permissions)) {
    //         return user.permissions.includes(perm);
    //     }

    //     return false; // Force false au lieu de undefined
    // };

    /**
     * 2. Utilisation de useMemo pour reconstruire et filtrer le menu dynamiquement
     */
    const filteredModel = useMemo(() => {
        const baseModel = [
            {
                label: 'Home',
                visible: true,
                items: [{ label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/', visible: true }]
            },
            {
                label: 'Administration',
                visible: hasModuleAccess('users') || hasModuleAccess('permissions') || hasModuleAccess('permissions.terrain'),
                items: [
                    {
                        label: 'Gestion Utilisateurs',
                        icon: 'pi pi-fw pi-users',
                        to: '/admin/users',
                        visible: hasModuleAccess('users')
                    },
                    // {
                    //     label: 'Utilisateurs et groupes',
                    //     icon: 'pi pi-fw pi-cog',
                    //     to: '/admin/assignments',
                    //     visible: hasModuleAccess('users') // Ou un autre module spécifique
                    // },
                    // {
                    //     label: 'Permissions des groupes',
                    //     icon: 'pi pi-fw pi-users',
                    //     to: '/admin/permissions-terrain',
                    //     visible: hasModuleAccess('permissions.terrain') // Un module spécifique pour la gestion des groupes
                    // },
                    {
                        label: 'Permissions des modules',
                        icon: 'pi pi-fw pi-shield',
                        to: '/admin/permissions',
                        visible: hasModuleAccess('permissions')
                    },
                    {
                        label: 'Configuration des Requis',
                        icon: 'pi pi-fw pi-cog',
                        to: '/admin/laboratoires/requis',
                        visible: hasModuleAccess('administration')
                    }
                ]
            },
            {
                label: 'Designations',
                visible: hasModuleAccess('designations'),
                items: [
                    {
                        label: 'Designations',
                        icon: 'pi pi-fw pi-cog',
                        to: '/designations-list',
                        visible: hasModuleAccess('designations')
                    },
                ]
            },
            // {
            //     label: 'Administration',
            //     // On peut imaginer un module 'administration' ou cumuler les tests
            //     visible: hasModuleAccess('users') || hasModuleAccess('permissions'),
            //     items: [
            //         {
            //             label: 'Gestion Utilisateurs',
            //             icon: 'pi pi-fw pi-users',
            //             to: '/admin/users',
            //             visible: hasModuleAccess('users') // Test dynamique du module 'users'
            //         },
            //         {
            //             label: 'Permissions des modules',
            //             icon: 'pi pi-fw pi-shield',
            //             to: '/admin/permissions',
            //             visible: hasModuleAccess('permissions')
            //         }
            //     ]
            // },
            // {
            //     label: 'Designations',
            //     // Le groupe parent se calque sur l'accès au module
            //     visible: hasModuleAccess('designations'),
            //     items: [
            //         {
            //             label: 'Designations',
            //             icon: 'pi pi-fw pi-cog',
            //             to: '/designations-list',
            //             visible: hasModuleAccess('designations') // Plus aucune référence en dur à Spatie !
            //         },
            //     ]
            // },
        ];

        // Fonction de filtrage récursive standard
        const filterMenu = (menuItems) => {
            return menuItems
                .filter(item => item.visible === true)
                .map(item => {
                    if (item.items) {
                        return { ...item, items: filterMenu(item.items) };
                    }
                    return item;
                })
                .filter(item => !item.items || item.items.length > 0);
        };

        return filterMenu(baseModel);

    }, [user]);

    return (
        <MenuProvider>
            <ul className="layout-menu">
                {filteredModel.map((item, i) => {
                    return !item?.seperator ? (
                        <AppMenuitem item={item} root={true} index={i} key={item.label} />
                    ) : (
                        <li className="menu-separator" key={`sep-${i}`}></li>
                    );
                })}
            </ul>
        </MenuProvider>
    );
};

export default AppMenu;

// /* eslint-disable @next/next/no-img-element */

// import React, { useContext } from 'react';
// import AppMenuitem from './AppMenuitem';
// import { LayoutContext } from './context/layoutcontext';
// import { MenuProvider } from './context/menucontext';
// import { Link, usePage } from '@inertiajs/react';
// //import Link from 'next/link';
// // import { AppMenuItem } from '@/types';

// const AppMenu = () => {
//     const { layoutConfig } = useContext(LayoutContext);

//     // On récupère les données partagées par Laravel
//     const { auth } = usePage().props;
//     const user = auth?.user;

//     // // Helper rapide pour vérifier les rôles
//     // const hasRole = (role) => auth.user?.roles?.includes(role);
//     // const hasPermission = (perm) => auth.user?.permissions?.includes(perm);


//     /**
//      * Helper de vérification des Rôles et Groupes adaptés à votre modèle
//      */
//     // Helper de vérification des Rôles (qui sont maintenant un tableau de chaînes grâce au pluck)
//     const hasRole = (roleName) => {
//         if (!user) return false;

//         // Admin global
//         if (user.is_admin) return true;

//         // Vérification dans le tableau de rôles Spatie (ex: ['admin', 'responsable'])
//         if (user.roles && user.roles.includes(roleName)) return true;

//         // Vérification dans les groupes custom (ex: [{code: 'admin', name: '...'}] )
//         if (user.groups && user.groups.some(g => g.code === roleName || g.name === roleName)) return true;

//         return false;
//     };
//     // const hasRole = (roleName) => {
//     //     if (!user) return false;

//     //     console.log('Vérification du rôle:', roleName);
//     //     console.log('Données utilisateur:', user);

//     //     // 1. S'il est admin via la colonne ou le cast booléen
//     //     if (user.is_admin) return true;

//     //     // 2. Si le rôle est stocké sous forme d'objets via Spatie (user.roles = [{name: 'admin', ...}])
//     //     if (user.roles && Array.isArray(user.roles)) {
//     //         if (user.roles.some(r => r.name === roleName || r.code === roleName)) return true;
//     //     }

//     //     // 3. Si le rôle correspond à vos groupes personnalisés (user.groups = [{code: 'admin', ...}])
//     //     if (user.groups && Array.isArray(user.groups)) {
//     //         if (user.groups.some(g => g.code === roleName || g.name === roleName)) return true;
//     //     }

//     //     console.warn(`Rôle "${roleName}" non trouvé pour l'utilisateur. Vérifiez les données de l'utilisateur et la logique de vérification des rôles.`);
//     //     // Fallback si c'est un tableau de chaînes simples
//     //     return user.roles?.includes?.(roleName) || user.groups?.includes?.(roleName);
//     // };

//     /**
//      * Helper de vérification des Permissions
//      */
//     const hasPermission = (perm) => {
//         if (!user) return false;
//         if (user.is_admin) return true;

//         // Si l'utilisateur appartient à l'administration ou direction, il a tout par défaut
//         if (user.groups?.some(g => g.name === 'Direction / Administration' || g.code === 'admin')) {
//             return true;
//         }

//         return user.permissions?.includes?.(perm);
//     };

//     const model = [
//         {
//             label: 'Home',
//             items: [{ label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/' }]
//         },
//         {
//             label: 'Administration',
//             visible: hasRole('admin') || hasPermission('config_lab_requis'),
//             items: [
//                 {
//                     label: 'Gestion Utilisateurs',
//                     icon: 'pi pi-fw pi-users',
//                     to: '/admin/users',
//                     visible: hasRole('admin') || hasPermission('view_users')
//                 },
//                 {
//                     label: 'Utilisateurs et groupes',
//                     icon: 'pi pi-fw pi-cog',
//                     to: '/admin/assignments',
//                     visible: hasRole('admin')
//                 },
//                 {
//                     label: 'Permissions des modules',
//                     icon: 'pi pi-fw pi-shield',
//                     to: '/admin/permissions',
//                     visible: hasRole('admin') || hasPermission('view_permissions')
//                 },
//                 {
//                     label: 'Configuration des Requis',
//                     icon: 'pi pi-fw pi-cog',
//                     to: '/admin/laboratoires/requis',
//                     visible: hasRole('admin') || hasPermission('config_lab_requis')
//                 }
//             ]
//         },
//         {
//             label: 'Designations',
//             visible: hasRole('admin') || hasPermission('view_designations') || hasRole('Responsable Labo') || hasRole('Technicien'),
//             items: [
//                 {
//                     label: 'Designations',
//                     icon: 'pi pi-fw pi-cog',
//                     to: '/designations-list',
//                     visible: hasRole('admin') || hasPermission('view_designations') || hasRole('Responsable Labo') || hasRole('Technicien')
//                 },
//             ]
//         },
//     ];
//     // const model = [
//     //     {
//     //         label: 'Home',
//     //         items: [{ label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/' }]
//     //     },
//     //     {
//     //         label: 'Administration',
//     //         // Le groupe entier disparaît si l'utilisateur n'est pas admin
//     //         // Le groupe est visible si admin OU s'il a la permission de voir les désignations
//     //         visible: hasRole('admin') || hasPermission('config_lab_requis'),
//     //         items: [
//     //             {
//     //                 label: 'Gestion Utilisateurs',
//     //                 icon: 'pi pi-fw pi-users',
//     //                 to: '/admin/users',
//     //                 // On peut aussi filtrer par permission précise
//     //                 visible: hasRole('admin') || hasPermission('view_users')
//     //             },
//     //             // {
//     //             //     label: 'Permissions des groupes',
//     //             //     icon: 'pi pi-fw pi-users',
//     //             //     to: '/admin/permissions-pivot',
//     //             //     visible: hasRole('admin') || hasPermission('view_groups')
//     //             // },
//     //             {
//     //                 label: 'Utiliateurs et groupes',
//     //                 icon: 'pi pi-fw pi-cog',
//     //                 to: '/admin/assignments',
//     //                 visible: hasRole('admin')
//     //             },
//     //             {
//     //                 label: 'Permissions des modules',
//     //                 icon: 'pi pi-fw pi-shield',
//     //                 to: '/admin/permissions',
//     //                 visible: hasRole('admin') || hasPermission('view_permissions')
//     //             },
//     //             {
//     //                 label: 'Configuration des Requis',
//     //                 icon: 'pi pi-fw pi-cog',
//     //                 to: '/admin/laboratoires/requis',
//     //                 visible: hasRole('admin') || hasPermission('config_lab_requis')
//     //             }
//     //         ]
//     //     },
//     //     {
//     //         label: 'Designations',
//     //         // Le groupe entier disparaît si l'utilisateur n'est pas admin
//     //         visible: hasRole('admin') || hasPermission('view_designations'),
//     //         items: [
//     //             // {
//     //             //     label: 'Designations1',
//     //             //     icon: 'pi pi-fw pi-cog',
//     //             //     to: '/designations',
//     //             //     visible: hasRole('admin') || hasPermission('view_designations')
//     //             // },
//     //             {
//     //                 label: 'Designations',
//     //                 icon: 'pi pi-fw pi-cog',
//     //                 to: '/designations-list',
//     //                 visible: hasRole('admin') || hasPermission('view_designations')
//     //             },
//     //         ]
//     //     },

//     //     // {
//     //     //     label: 'UI Components',
//     //     //     items: [
//     //     //         { label: 'Form Layout', icon: 'pi pi-fw pi-id-card', to: '/uikit/formlayout' },
//     //     //         { label: 'Input', icon: 'pi pi-fw pi-check-square', to: '/uikit/input' },
//     //     //         { label: 'Float Label', icon: 'pi pi-fw pi-bookmark', to: '/uikit/floatlabel' },
//     //     //         { label: 'Invalid State', icon: 'pi pi-fw pi-exclamation-circle', to: '/uikit/invalidstate' },
//     //     //         { label: 'Button', icon: 'pi pi-fw pi-mobile', to: '/uikit/button', class: 'rotated-icon' },
//     //     //         { label: 'Table', icon: 'pi pi-fw pi-table', to: '/uikit/table' },
//     //     //         { label: 'List', icon: 'pi pi-fw pi-list', to: '/uikit/list' },
//     //     //         { label: 'Tree', icon: 'pi pi-fw pi-share-alt', to: '/uikit/tree' },
//     //     //         { label: 'Panel', icon: 'pi pi-fw pi-tablet', to: '/uikit/panel' },
//     //     //         { label: 'Overlay', icon: 'pi pi-fw pi-clone', to: '/uikit/overlay' },
//     //     //         { label: 'Media', icon: 'pi pi-fw pi-image', to: '/uikit/media' },
//     //     //         { label: 'Menu', icon: 'pi pi-fw pi-bars', to: '/uikit/menu', preventExact: true },
//     //     //         { label: 'Message', icon: 'pi pi-fw pi-comment', to: '/uikit/message' },
//     //     //         { label: 'File', icon: 'pi pi-fw pi-file', to: '/uikit/file' },
//     //     //         { label: 'Chart', icon: 'pi pi-fw pi-chart-bar', to: '/uikit/charts' },
//     //     //         { label: 'Misc', icon: 'pi pi-fw pi-circle', to: '/uikit/misc' }
//     //     //     ]
//     //     // },
//     //     // {
//     //     //     label: 'Prime Blocks',
//     //     //     items: [
//     //     //         { label: 'Free Blocks', icon: 'pi pi-fw pi-eye', to: '/blocks', badge: 'NEW' },
//     //     //         { label: 'All Blocks', icon: 'pi pi-fw pi-globe', url: 'https://blocks.primereact.org', target: '_blank' }
//     //     //     ]
//     //     // },
//     //     // {
//     //     //     label: 'Utilities',
//     //     //     items: [
//     //     //         { label: 'PrimeIcons', icon: 'pi pi-fw pi-prime', to: '/utilities/icons' },
//     //     //         { label: 'PrimeFlex', icon: 'pi pi-fw pi-desktop', url: 'https://primeflex.org/', target: '_blank' }
//     //     //     ]
//     //     // },
//     //     // {
//     //     //     label: 'Pages',
//     //     //     icon: 'pi pi-fw pi-briefcase',
//     //     //     to: '/pages',
//     //     //     items: [
//     //     //         {
//     //     //             label: 'Landing',
//     //     //             icon: 'pi pi-fw pi-globe',
//     //     //             to: '/landing'
//     //     //         },
//     //     //         {
//     //     //             label: 'Auth',
//     //     //             icon: 'pi pi-fw pi-user',
//     //     //             items: [
//     //     //                 {
//     //     //                     label: 'Login',
//     //     //                     icon: 'pi pi-fw pi-sign-in',
//     //     //                     to: '/auth/login'
//     //     //                 },
//     //     //                 {
//     //     //                     label: 'Error',
//     //     //                     icon: 'pi pi-fw pi-times-circle',
//     //     //                     to: '/auth/error'
//     //     //                 },
//     //     //                 {
//     //     //                     label: 'Access Denied',
//     //     //                     icon: 'pi pi-fw pi-lock',
//     //     //                     to: '/auth/access'
//     //     //                 }
//     //     //             ]
//     //     //         },
//     //     //         {
//     //     //             label: 'Crud',
//     //     //             icon: 'pi pi-fw pi-pencil',
//     //     //             to: '/pages/crud'
//     //     //         },
//     //     //         {
//     //     //             label: 'Timeline',
//     //     //             icon: 'pi pi-fw pi-calendar',
//     //     //             to: '/pages/timeline'
//     //     //         },
//     //     //         {
//     //     //             label: 'Not Found',
//     //     //             icon: 'pi pi-fw pi-exclamation-circle',
//     //     //             to: '/pages/notfound'
//     //     //         },
//     //     //         {
//     //     //             label: 'Empty',
//     //     //             icon: 'pi pi-fw pi-circle-off',
//     //     //             to: '/pages/empty'
//     //     //         }
//     //     //     ]
//     //     // },
//     //     // {
//     //     //     label: 'Hierarchy',
//     //     //     items: [
//     //     //         {
//     //     //             label: 'Submenu 1',
//     //     //             icon: 'pi pi-fw pi-bookmark',
//     //     //             items: [
//     //     //                 {
//     //     //                     label: 'Submenu 1.1',
//     //     //                     icon: 'pi pi-fw pi-bookmark',
//     //     //                     items: [
//     //     //                         { label: 'Submenu 1.1.1', icon: 'pi pi-fw pi-bookmark' },
//     //     //                         { label: 'Submenu 1.1.2', icon: 'pi pi-fw pi-bookmark' },
//     //     //                         { label: 'Submenu 1.1.3', icon: 'pi pi-fw pi-bookmark' }
//     //     //                     ]
//     //     //                 },
//     //     //                 {
//     //     //                     label: 'Submenu 1.2',
//     //     //                     icon: 'pi pi-fw pi-bookmark',
//     //     //                     items: [{ label: 'Submenu 1.2.1', icon: 'pi pi-fw pi-bookmark' }]
//     //     //                 }
//     //     //             ]
//     //     //         },
//     //     //         {
//     //     //             label: 'Submenu 2',
//     //     //             icon: 'pi pi-fw pi-bookmark',
//     //     //             items: [
//     //     //                 {
//     //     //                     label: 'Submenu 2.1',
//     //     //                     icon: 'pi pi-fw pi-bookmark',
//     //     //                     items: [
//     //     //                         { label: 'Submenu 2.1.1', icon: 'pi pi-fw pi-bookmark' },
//     //     //                         { label: 'Submenu 2.1.2', icon: 'pi pi-fw pi-bookmark' }
//     //     //                     ]
//     //     //                 },
//     //     //                 {
//     //     //                     label: 'Submenu 2.2',
//     //     //                     icon: 'pi pi-fw pi-bookmark',
//     //     //                     items: [{ label: 'Submenu 2.2.1', icon: 'pi pi-fw pi-bookmark' }]
//     //     //                 }
//     //     //             ]
//     //     //         }
//     //     //     ]
//     //     // },
//     //     // {
//     //     //     label: 'Get Started',
//     //     //     items: [
//     //     //         {
//     //     //             label: 'Documentation',
//     //     //             icon: 'pi pi-fw pi-question',
//     //     //             to: '/documentation'
//     //     //         },
//     //     //         {
//     //     //             label: 'Figma',
//     //     //             url: 'https://www.dropbox.com/scl/fi/bhfwymnk8wu0g5530ceas/sakai-2023.fig?rlkey=u0c8n6xgn44db9t4zkd1brr3l&dl=0',
//     //     //             icon: 'pi pi-fw pi-pencil',
//     //     //             target: '_blank'
//     //     //         },
//     //     //         {
//     //     //             label: 'View Source',
//     //     //             icon: 'pi pi-fw pi-search',
//     //     //             url: 'https://github.com/primefaces/sakai-react',
//     //     //             target: '_blank'
//     //     //         }
//     //     //     ]
//     //     // }
//     // ];

//     /**
//      * Nettoyage récursif de l'arbre du menu
//      * Élimine les sous-éléments invisibles et retire les sections vides
//      */
//     const filterMenu = (menuItems) => {
//         return menuItems
//             // 1. On vire immédiatement l'élément s'il est explicitement invisible
//             .filter(item => item.visible !== false)
//             .map(item => {
//                 // 2. Si l'élément contient des sous-éléments (ex: la catégorie Administration),
//                 // on applique le même filtre sur ses enfants de manière récursive
//                 if (item.items) {
//                     return { ...item, items: filterMenu(item.items) };
//                 }
//                 return item;
//             })
//             // 3. Sécurité cruciale : si une catégorie parent n'a plus AUCUN enfant visible
//             // suite au filtrage (ex: un technicien qui n'a aucun droit dans Administration),
//             // on supprime complètement la catégorie pour ne pas afficher un titre vide.
//             .filter(item => !item.items || item.items.length > 0);
//     };

//     const filteredModel = filterMenu(model);

//     return (
//         <MenuProvider>
//             <ul className="layout-menu">
//                 {filteredModel.map((item, i) => {
//                     return !item?.seperator ? (
//                         <AppMenuitem item={item} root={true} index={i} key={item.label} />
//                     ) : (
//                         <li className="menu-separator" key={`sep-${i}`}></li>
//                     );
//                 })}
//             </ul>
//         </MenuProvider>
//     );
// };

// // return (
// //     <MenuProvider>
// //         <ul className="layout-menu">
// //             {model.map((item, i) => {
// //                 return !item?.seperator ? <AppMenuitem item={item} root={true} index={i} key={item.label} /> : <li className="menu-separator"></li>;
// //             })}

// //             {/* <Link href="https://blocks.primereact.org" target="_blank" style={{ cursor: 'pointer' }}>
// //                 <img alt="Prime Blocks" className="w-full mt-3" src={`/layout/images/banner-primeblocks${layoutConfig.colorScheme === 'light' ? '' : '-dark'}.png`} />
// //             </Link> */}
// //         </ul>
// //     </MenuProvider>
// // );
// //};

// export default AppMenu;
