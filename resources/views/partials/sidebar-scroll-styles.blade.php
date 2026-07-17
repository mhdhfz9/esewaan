<style>
    .sidebar-scroll {
        scrollbar-gutter: stable;
        scrollbar-width: thin;
        scrollbar-color: transparent transparent;
    }

    .sidebar-scroll:hover,
    .sidebar-scroll:focus-within {
        scrollbar-color: rgb(255 255 255 / 0.25) transparent;
    }

    .sidebar-scroll::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar-scroll::-webkit-scrollbar-track {
        margin-block: 10px;
        background: transparent;
    }

    .sidebar-scroll::-webkit-scrollbar-thumb {
        background-color: rgb(255 255 255 / 0.12);
        border-radius: 9999px;
        transition: background-color 0.15s ease;
    }

    .sidebar-scroll:hover::-webkit-scrollbar-thumb {
        background-color: rgb(255 255 255 / 0.22);
    }

    .sidebar-scroll::-webkit-scrollbar-thumb:active {
        background-color: rgb(255 255 255 / 0.35);
    }
</style>
