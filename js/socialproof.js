(() => {
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.product-social-proof').forEach(async container => {
            const product = container.dataset.product;
            const intervalTime = parseInt(container.dataset.interval ?? 2000);

            const geos = JSON.parse(document.querySelector('#product_social_proof_geos').textContent)
            const names = JSON.parse(document.querySelector('#product_social_proof_persons_names').textContent)

            // console.log('[SOCIALPROOF] geos: ', geos)
            // console.log('[SOCIALPROOF] names: ', names)

            const getRandomFromArrayOfObjects = (a) => a[Math.floor(Math.random() * a.length)]

            const updateContent = () => {
                const randomGeo = getRandomFromArrayOfObjects(geos);
                const randomName = getRandomFromArrayOfObjects(names);

                const firstName = randomName.firstname;
                const lastName = randomName.lastname;

                const name = `${firstName} ${lastName[0]}.`;
                
                const city = randomGeo.name.replaceAll("città metropolitana di", "");

                const minutesAgo = Math.floor(Math.random() * 15) + 1;
                
                container.innerHTML = `<span class="psp--name">${name}</span> da <span class="psp--city">${city}</span> ha ordinato <span class="psp--product">${product}</span> <span class="psp--time">${minutesAgo}</span> minuti fa`;
            };

            // Initial update
            updateContent();

            // Update every 500ms
            setInterval(updateContent, intervalTime);
        });
    });
})();