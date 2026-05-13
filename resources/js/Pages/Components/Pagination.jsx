export default function Pagination({ links, onPageChange }) {

    // Si links n'existe pas ou est vide, on ne retourne rien
    if (!links || links.length === 0) {
        return null;
    }

    return (
        <nav className="flex space-x-1">
            {links.map((link, key) => {
                const isPageNumber = !isNaN(link.label) || link.label.includes('...');
                // On vérifie que link.label existe pour éviter une erreur sur .includes
                const label = link.label || '';
                return (
                    <button
                        key={key}
                        disabled={!link.url || link.active}
                        onClick={() => {
                            if (link.url) {
                                const url = new URL(link.url);
                                onPageChange(url.searchParams.get('page'));
                            }
                        }}
                        className={`px-3 py-1 border rounded text-sm transition-colors ${link.active
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300'
                            } ${!link.url ? 'text-gray-300 cursor-not-allowed' : 'cursor-pointer'}`}
                        dangerouslySetInnerHTML={{ __html: label }}
                    />
                );
            })}
        </nav>
    );
}