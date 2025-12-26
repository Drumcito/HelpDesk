function pollAvailability() {
    fetch('/HelpDesk_EQF/modules/dashboard/admin/availability_data.php')
        .then(r => r.json())
        .then(data => {
            if (!data || !data.users) return;

            data.users.forEach(u => {
                const card = document.querySelector(
                  `.analyst-card[data-user-id="${u.user_id}"]`
                );
                if (!card) return;

                card.setAttribute('data-status', u.status);
                card.querySelector('.status-label').textContent = u.label;
            });
        })
        .catch(console.error);
}

// cada 10 segundos
setInterval(pollAvailability, 10000);

// primera carga inmediata
pollAvailability();
