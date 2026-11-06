const scrollToCatalogProduct = () => {
    const id = window.location.hash.slice(1);

    if (id.startsWith('product-')) {
        document.getElementById(id)?.scrollIntoView({ block: 'start' });
    }
};

window.addEventListener('load', scrollToCatalogProduct);
window.addEventListener('hashchange', scrollToCatalogProduct);
