export function hasRole(auth, role) {
    return auth.user?.roles?.includes(role);
}