<footer>
    <div class="box__copyright">
        <p>
            Todos los derechos reservados ©2025 
            <b>Equilibrio Farmacéutico</b> 
            <span class="logoNR"></span>
        </p>
    </div>

    <?php
    $eqf_footer_loaded = true;

    if ($eqf_footer_loaded) {
        echo '<style>
            .logoNR::after {
                content: "' . base64_decode("QnJhbmRvbiBTdcOhcmV6IOKgIERhZm5lIExhaWxzb24=") . '";
                opacity: 0.18;
                font-size: 11px;
                margin-left: 8px;
                user-select: none;
                pointer-events: none;
                position: relative;
                top: -1px;
            }
        </style>';
    }
    ?>
</footer>
