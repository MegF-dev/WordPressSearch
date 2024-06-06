jQuery(document).ready(function ($) {
  var debounceTimer;
  var $inputField = $("#custom-live-search-field");

  $("#custom-live-search-field").on("keyup", function () {
    var searchTerm = $(this).val();
    if (searchTerm.length > 0) {
      $inputField.css("background-color", "white");
    } else {
      $inputField.css("background-color", "");
    }
    clearTimeout(debounceTimer);

    debounceTimer = setTimeout(function () {
      if (searchTerm.length > 0) {
        $("#custom-loader").show();
        performSearch(searchTerm);
      } else {
        $("#custom-live-search-results").hide();
        $("#custom-loader").hide();
      }
    }, 1000);
  });
  function stripTerms(text) {
    return text.toLowerCase().replace(/&|and/g, "");
  }

  function performSearch(searchTerm) {
    console.log("Search Term: " + searchTerm);

    var searchTerms = searchTerm.toLowerCase().split(" "); // Split into words for individual checking
    console.log(searchTerms);
    $.ajax({
      url: ajax_object.ajaxurl,
      type: "POST",
      data: {
        action: "custom_product_search",
        term: searchTerm,
        frontEndSearch: true,
      },
      success: function (response) {
        console.log(response);
        var resultHtml = "";

        // if (response.is_brand_search) {
        //   // Hide categories and show brands
        //   $(".search-list-categories").hide();
        //   $(".search-list-brands").show();
        // } else {
        //   // Show categories and hide brands
        //   $(".search-list-categories").show();
        //   $(".search-list-brands").hide();
        // }
        var categories = response.categories || [];
        var tags = response.tags || [];
        var searchtags = response.search_tag || [];

        var combinedCategoriesAndTags = categories.concat(tags, searchtags);
        if (combinedCategoriesAndTags.length > 0) {
          resultHtml +=
            "<ul class='search-list-categories'><h4>Popular Brands & Categories</h4>";
          $.each(combinedCategoriesAndTags, function (index, category) {
            if (category.display_name) {
              resultHtml += "<li><a href='" + category.link + "'>";
              if (category.category_image) {
                resultHtml +=
                  '<img src="' +
                  category.category_image +
                  '" alt="' +
                  category.display_name +
                  '">';
              } else {
                // Display a placeholder image or a default category icon if no image is available
                resultHtml +=
                  '<img src="path/to/placeholder-image.jpg" alt="' +
                  category.display_name +
                  '">';
              }
              resultHtml += category.display_name + "</a></li>";
            }
          });
          resultHtml += "</ul>";
        }
        if (searchTerm.startsWith("le creuset ")) {
          setTimeout(function () {
            $(".search-list-categories").hide();
            console.log("categories hidden");
          }, 500);
        }

        if (
          searchTerm.startsWith("le creuset ") ||
          searchTerm.startsWith("carrol boyes  ") ||
          searchTerm.startsWith("casa domani ") ||
          searchTerm.startsWith("chef & sommelier ") ||
          searchTerm.startsWith("chef and sommelier ") ||
          searchTerm.startsWith("jamie oliver ") ||
          searchTerm.startsWith("jenna clifford ") ||
          searchTerm.startsWith("kitchen inspire ") ||
          searchTerm.startsWith("mason cash ") ||
          searchTerm.startsWith("nicolson russell ") ||
          searchTerm.startsWith("luigi bormioli ") ||
          searchTerm.startsWith("wilkinson sword ") ||
          searchTerm.startsWith("souper cubes ") ||
          searchTerm.startsWith("snap wrap ") ||
          searchTerm.startsWith("st. tropez ") ||
          searchTerm.startsWith("st tropez ") ||
          searchTerm.startsWith("work sharp ") ||
          searchTerm.startsWith("snappy chef ") ||
          searchTerm.startsWith("instant pot ") ||
          searchTerm.startsWith("ken hom ") ||
          searchTerm.startsWith("home classix ") ||
          searchTerm.startsWith("risen solar ") ||
          searchTerm.startsWith("russell hobbs ") ||
          searchTerm.startsWith("maxwell and williams") ||
          searchTerm.startsWith("maxwell & williams") ||
          searchTerm.startsWith("maxwell and williams ") ||
          searchTerm.startsWith("maxwell & williams")
        ) {
          setTimeout(function () {
            $(".search-list-categories").hide();
            console.log("categories hidden");
          }, 1000);
        }
        if (response.brands.length > 0) {
          resultHtml += "<ul  class='search-list-brands'>";
          $.each(response.brands, function (index, brand) {
            resultHtml += "<li><a href='" + brand.permalink + "'>";

            resultHtml += "<span>" + brand.name + "</span></a>";
            resultHtml += "</li>";
          });
          resultHtml += "</ul>";
        }
        if (response.child_brands && response.child_brands.length > 0) {
          resultHtml += "<ul class='search-list-child-brands'>";
          $.each(response.child_brands, function (index, child) {
            resultHtml += "<li><a href='" + child.permalink + "'>";
            resultHtml += child.image_url
              ? "<img src='" + child.image_url + "' alt='" + child.name + "'>"
              : "";
            resultHtml += child.name + "</a></li>";
          });
          resultHtml += "</ul>";
        }
        if (response.products && response.products.length > 0) {
          resultHtml +=
            "<ul class='search-list-popular-products'><h4>Popular Products</h4>";
          $.each(response.products, function (index, product) {
            resultHtml += '<li><a href="' + product.permalink + '">';
            if (product.image) {
              resultHtml +=
                '<img src="' + product.image + '" alt="' + product.title + '">';
            }
            resultHtml +=
              "<span class='brand-results'>" +
              (product.brand ? product.brand : "") +
              "</span>";
            resultHtml +=
              "<span class='title-results'>" + product.title + "</span>";
            resultHtml += "</a></li>";
          });
          resultHtml += "</ul>";
        }

        setTimeout(function () {
          if (response.is_brand_search) {
            console.log("is a brand search");
            $(".search-list-brands").prepend(
              "<h4>Popular Brands & Categories</h4>"
            );
            $(".search-list-categories").hide();
            $(".search-list-brands").show();
            $(".search-list-child-brands li:nth-child(n+6)").hide();
            if ($(".search-list-popular-products").length === 0) {
              $(".view-more-results").hide();
            }
          } else {
            console.log("is not a brand search");

            if ($(".search-list-popular-products").length === 0) {
              $(".view-more-results").hide();
            }
            $(".search-list-categories").show();
            function toSingular(word) {
              word = word.toLowerCase();
              $(".search-list-categories li:nth-child(n+6)").hide();

              const exceptions = {
                cookies: "cookie",
                geese: "goose",
                men: "man",
                women: "woman",
                children: "child",
                teeth: "tooth",
                feet: "foot",
              };

              // Check if the word is an exception
              if (exceptions[word]) {
                return exceptions[word];
              }

              if (word.endsWith("ies")) {
                return word.substring(0, word.length - 3) + "y";
              } else if (word.endsWith("es")) {
                if (
                  word.endsWith("xes") ||
                  word.endsWith("ses") ||
                  word.endsWith("ches") ||
                  word.endsWith("shes")
                ) {
                  return word.substring(0, word.length - 2);
                }
              } else if (word.endsWith("s") && !word.endsWith("ss")) {
                return word.substring(0, word.length - 1);
              }

              return word;
            }

            function processSearchTerms(searchTerm) {
              var searchTerms = searchTerm.toLowerCase().split(" ");
              var singularTerms = searchTerms.map(toSingular);
              return singularTerms;
            }

            function filterList(searchTerm) {
              var singularizedTerms = processSearchTerms(searchTerm);
              var $listItems = $("ul.search-list-categories li");
              var $heading = $("ul.search-list-categories h4");

              var allHidden = true;

              $listItems.each(function () {
                var itemText = $(this).text().toLowerCase();
                var shouldShow = singularizedTerms.some(function (term) {
                  return itemText.includes(term);
                });

                if (shouldShow) {
                  $(this).show();
                  allHidden = false;
                } else {
                  $(this).hide();
                }
              });

              if (allHidden) {
                $heading.hide();
              } else {
                $heading.show();
              }
            }
            filterList(searchTerm);
            // $(".search-list-categories li:nth-child(n+6)").hide();
            $(".search-list-brands").hide();
            $(".search-list-child-brands li:nth-child(n+6)").hide();
          }
        }, 50);
        if (resultHtml === "") {
          resultHtml = "<p>No results found.</p>";
        } else {
          resultHtml +=
            "<a href='/?s=" +
            encodeURIComponent(searchTerm) +
            "' class='view-more-results'>View More Products</a>";
        }

        $("#custom-loader").hide(); // Hide the loader when data is received
        $("#custom-live-search-results").html(resultHtml).show();
      },
      error: function (xhr, status, error) {
        console.error("Error: " + error);
        $("#custom-live-search-results")
          .html("<p>Error fetching results. Please try again later.</p>")
          .show();
        $("#custom-loader").hide();
      },
    });
  }
});
jQuery(document).ready(function ($) {
  var $modal = $("#search-modal");

  $("#open-search-modal").on("click", function () {
    $modal.css("display", "block");
    $("#custom-live-search-field").focus();
  });

  $(".close-modal").on("click", function () {
    $modal.css("display", "none");
  });

  $(window).on("click", function (event) {
    if (event.target === $modal[0]) {
      $modal.css("display", "none");
    }
  });
  var liveSearchField = $("#custom-live-search-field");
  var normalSearchButton = $("#normal-search");

  normalSearchButton.on("click", function (event) {
    event.preventDefault();

    var searchTerm = liveSearchField.val().trim();
    if (searchTerm) {
      window.location.href = "/?s=" + encodeURIComponent(searchTerm);
    }
  });

  $(".close-modal").on("click", function () {
    $("#custom-live-search-field").val("");
    $("#custom-live-search-results").empty();
  });
  var resultCount = $("#custom-live-search-results").children().length;
  if (resultCount < 3) {
    $("#custom-live-search-results").append("<p>No Results Found</p>");
  }
});
