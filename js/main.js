jQuery(document).ready(function ($) {
    $("img").removeAttr("title");

    //*
    //*
    //==============================HOMEPAGE=========================================//
    //*
    //*

    // VIEW MORE/ VIEW LESS BUTTON FUNCTIONALITY ON THE PRODUCT GRID ON THE HOME PAGE

    $("#view-less").hide();
    $(".view-all-products").hide();
    $("#view-all").click(function () {
        $(".view-all-products").fadeIn();
        $("#view-all").hide();
        $("#view-less").fadeIn();
        $(".loop-item-product-card").hide();
    });

    $("#view-less").click(function () {
        $(this).hide();
        $(".view-all-products").hide();
        $("#view-all").fadeIn();
        $(".loop-item-product-card").fadeIn();
    });

    //*
    //==============================FOOTER LINK LOGIC=========================================//

    //*

    $("#best-sellers-footer").on("click", function (e) {
        e.preventDefault();
        $("html, body").animate(
            {
                scrollTop: $("#product-display").offset().top,
            },
            "slow",
            function () {
                $("#best-sellers.eael-tab-item-trigger").click();
            }
        );
    });
    $(".new-arrival-button").on("click", function (e) {
        e.preventDefault();
        $("html, body").animate(
            {
                scrollTop: $("#product-display").offset().top,
            },
            "slow",
            function () {
                $("#new-arrivals.eael-tab-item-trigger").click();
            }
        );
    });
    $(".special-offer-button").on("click", function (e) {
        e.preventDefault();
        $("html, body").animate(
            {
                scrollTop: $("#product-display").offset().top,
            },
            "slow",
            function () {
                $("#special-offers.eael-tab-item-trigger").click();
            }
        );
    });
    $("#featured-footer").on("click", function (e) {
        e.preventDefault();
        $("html, body").animate({
            scrollTop: $("#featured-products").offset().top,
        });
    });
    //*
    //==============================PRODUCT CARDS ON THE HOME PAGE=========================================//

    //*
    // THIS TRUNCATES THE PRODUCT TITLE ON THE PRODUCT CARD IF IT IS MORE THAN 9 WORDS LONG

    $(".product_title a").each(function () {
        var $title = $(this);
        var titleText = $title.text();
        var words = titleText.split(" ");

        if (words.length > 9) {
            var truncatedTitle = words.slice(0, 9).join(" ") + "...";
            $title.text(truncatedTitle);
        }
    });
});
// THIS DRIVES THE "NEW" BADGE ON THE PRODUCT CARD ON THE HOME PAGE
// note: for product cards on the shop pages please see /product-card/product-card-functions.php
jQuery(document).ready(function ($) {
    const currentDate = new Date();

    $(".single-product-loop-item").each(function () {
        const dateElement = $(this).find(
            ".elementor-post-info__item--type-date"
        );

        const dateString = dateElement.text().trim();

        const postDate = new Date(dateString);

        const diffTime = Math.abs(currentDate - postDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays <= 30) {
            $(this)
                .find(".loop-product-image")
                .append('<div class="product-grid-new-badge">New</div>');
        }
        const delElement = $(this).find("del");
        if (delElement.length > 0) {
            $(this)
                .find(".loop-product-image")
                .append('<div class="product-grid-sale-badge">Sale</div>');
        }
    });
});
// THIS DRIVES THE "SALE" BADGE ON THE PRODUCT CARD ON THE HOME PAGE
// note: for logic of the product cards on the shop pages please see /product-card/product-card-functions.php
jQuery(document).ready(function ($) {
    $(".loop-item-product-card p.price,.view-all-products p.price").each(
        function () {
            const delElement = $(this).find("del .woocommerce-Price-amount");
            const insElement = $(this).find("ins .woocommerce-Price-amount");

            if (delElement.length > 0 && insElement.length > 0) {
                const delValue = parseFloat(
                    delElement.text().replace("R", "").replace(",", "")
                );
                const insValue = parseFloat(
                    insElement.text().replace("R", "").replace(",", "")
                );

                const savings = delValue - insValue;
                if (savings > 0) {
                    const savingsText = "Save R" + savings.toFixed(2);
                    $(this).append(
                        '<span class="savings-block">' + savingsText + "</span>"
                    );
                }
            }
        }
    );
});
// THIS DISPLAYS DIFFERENT SHIPPING OPTIONS DEPENDING ON THE CART TOTAL
jQuery(document).ready(function ($) {
    function checkAndHideShipping() {
        var cartTotalRaw = $(".woocommerce-Price-amount bdi").last().text();
        var cartTotal = parseFloat(cartTotalRaw.replace(/[^\d.]/g, ""));

        if (cartTotal > 500) {
            $("input[id*='shipping_method_0_flat_rate6']").closest("li").hide();
            $("input[id*='shipping_method_0_free_shipping1']")
                .closest("li")
                .show();
        } else {
            $("input[id*='shipping_method_0_flat_rate6']").closest("li").show();
            $("input[id*='shipping_method_0_free_shipping1']")
                .closest("li")
                .hide();
        }
    }

    checkAndHideShipping();

    $(document.body).on(
        "change",
        'input[name="shipping_method[0]"]',
        checkAndHideShipping
    );
    $(document.body).on("updated_cart_totals", checkAndHideShipping);
    $(document).ajaxComplete(function (event, xhr, settings) {
        checkAndHideShipping();
    });
});

jQuery(document).ready(function ($) {
    setTimeout(function () {
        $(".elementor-menu-cart__product").each(function () {
            var productPrice = $(this).find(
                ".elementor-menu-cart__product-price"
            );

            if (productPrice.length) {
                var priceText = productPrice.text().trim();

                if (priceText.includes("0.00")) {
                    productPrice.hide();
                }
            }
        });
    }, 1000);
});
jQuery(document).ready(function ($) {
    $(".open-registry-popup a").on("click", function (e) {
        e.preventDefault();
        $(".find-a-registry-btn span").click();
    });
});
jQuery(document).ready(function ($) {
    function checkRegistryProductsAndHideShipping() {
        var allProductsAreRegistry = true;

        $(".cart_item").each(function () {
            if (
                $(this).find(".product-name .variation-RegistryName").length ===
                0
            ) {
                allProductsAreRegistry = false;
                return false;
            }
        });

        if (allProductsAreRegistry) {
            $(".woocommerce-shipping-totals").hide();
            $(".shipping-area").hide();
            $(".shipping-area").addClass("hide-shipping");
        } else {
            $(".woocommerce-shipping-totals").show();
            $(".shipping-area").css("display", "flex !important");
        }
    }

    checkRegistryProductsAndHideShipping();

    $(document.body).on(
        "change",
        'input[name="shipping_method[0]"]',
        checkRegistryProductsAndHideShipping
    );
    $(document.body).on(
        "updated_cart_totals",
        checkRegistryProductsAndHideShipping
    );
    $(document).ajaxComplete(function (event, xhr, settings) {
        checkRegistryProductsAndHideShipping();
    });
});
