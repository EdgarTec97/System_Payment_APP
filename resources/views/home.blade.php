@extends('layouts.app')

@section('title', '- Inicio')

@section('content')
<!-- Hero Section -->
<section class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-5">
    <div class="container">
        <div class="row align-items-center min-vh-50">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4 animate__animated animate__fadeInUp">
                    Bienvenido a {{ config('app.name') }}
                </h1>
                <p class="lead mb-4 animate__animated animate__fadeInUp animate__delay-1s">
                    Descubre los mejores productos con la mejor calidad y servicio.
                    Tu satisfacción es nuestra prioridad.
                </p>
                <div class="animate__animated animate__fadeInUp animate__delay-2s">
                    <a href="{{ route('products.index') }}" class="btn btn-warning btn-lg me-3 shadow-lg">
                        <i class="bi bi-shop me-2"></i>Ver Productos
                    </a>
                    @guest
                    <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg shadow-lg">
                        <i class="bi bi-person-plus me-2"></i>Registrarse
                    </a>
                    @endguest
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="animate__animated animate__fadeInRight animate__delay-1s">
                    <i class="bi bi-shop display-1 text-warning opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="display-5 fw-bold text-dark mb-3">¿Por qué elegirnos?</h2>
                <p class="lead text-muted">Ofrecemos la mejor experiencia de compra en línea</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm hover-lift">
                    <div class="card-body text-center p-4">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-truck text-primary fs-1"></i>
                        </div>
                        <h5 class="card-title fw-bold">Envío Rápido</h5>
                        <p class="card-text text-muted">
                            Entrega en 24-48 horas en toda la ciudad.
                            Envío gratuito en compras superiores a $50.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm hover-lift">
                    <div class="card-body text-center p-4">
                        <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-shield-check text-success fs-1"></i>
                        </div>
                        <h5 class="card-title fw-bold">Compra Segura</h5>
                        <p class="card-text text-muted">
                            Tus datos están protegidos con encriptación SSL.
                            Múltiples métodos de pago seguros.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm hover-lift">
                    <div class="card-body text-center p-4">
                        <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-headset text-warning fs-1"></i>
                        </div>
                        <h5 class="card-title fw-bold">Soporte 24/7</h5>
                        <p class="card-text text-muted">
                            Nuestro equipo está disponible las 24 horas
                            para ayudarte con cualquier consulta.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products -->
@if(isset($featuredProducts) && $featuredProducts->count() > 0)
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="display-5 fw-bold text-dark mb-3">Productos Destacados</h2>
                <p class="lead text-muted">Los productos más populares con descuentos especiales</p>
            </div>
        </div>

        <div class="row g-4">
            @foreach($featuredProducts as $product)
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm hover-lift product-card">
                    @if($product->primaryImage)
                    <img src="{{ $product->primaryImage->url }}"
                        class="card-img-top"
                        alt="{{ $product->title }}"
                        style="height: 200px; object-fit: cover;">
                    @else
                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                        <i class="bi bi-image text-muted fs-1"></i>
                    </div>
                    @endif

                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title fw-bold">{{ $product->title }}</h6>
                        <p class="card-text text-muted small flex-grow-1">
                            {{ Str::limit($product->description, 80) }}
                        </p>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                @if($product->discount > 0)
                                <span class="text-muted text-decoration-line-through small">
                                    ${{ number_format($product->price, 2) }}
                                </span>
                                <span class="fw-bold text-success fs-5">
                                    ${{ number_format($product->final_price, 2) }}
                                </span>
                                <span class="badge bg-danger ms-1">
                                    -{{ $product->discount }}%
                                </span>
                                @else
                                <span class="fw-bold text-dark fs-5">
                                    ${{ number_format($product->final_price, 2) }}
                                </span>
                                @endif
                            </div>

                            @if($product->stock > 0)
                            <small class="text-success">
                                <i class="bi bi-check-circle me-1"></i>En stock
                            </small>
                            @else
                            <small class="text-danger">
                                <i class="bi bi-x-circle me-1"></i>Agotado
                            </small>
                            @endif
                        </div>

                        <div class="d-grid gap-2">
                            <a href="{{ route('products.show', $product) }}"
                                class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye me-1"></i>Ver Detalles
                            </a>

                            @auth
                            @if($product->stock > 0)
                            <button type="button"
                                class="btn btn-primary btn-sm"
                                onclick="addToCart({{ $product->id }})">
                                <i class="bi bi-cart-plus me-1"></i>Agregar al Carrito
                            </button>
                            @else
                            <button type="button" class="btn btn-secondary btn-sm" disabled>
                                <i class="bi bi-x-circle me-1"></i>No Disponible
                            </button>
                            @endif
                            @else
                            <a href="{{ route('login') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar Sesión
                            </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-5">
            <a href="{{ route('products.index') }}" class="btn btn-primary btn-lg">
                <i class="bi bi-grid me-2"></i>Ver Todos los Productos
            </a>
        </div>
    </div>
</section>
@endif

<!-- Statistics Section -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-lg-3 col-md-6">
                <div class="d-flex flex-column align-items-center">
                    <i class="bi bi-people fs-1 mb-3 text-warning"></i>
                    <h3 class="fw-bold">10,000+</h3>
                    <p class="mb-0">Clientes Satisfechos</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="d-flex flex-column align-items-center">
                    <i class="bi bi-box-seam fs-1 mb-3 text-warning"></i>
                    <h3 class="fw-bold">5,000+</h3>
                    <p class="mb-0">Productos Disponibles</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="d-flex flex-column align-items-center">
                    <i class="bi bi-truck fs-1 mb-3 text-warning"></i>
                    <h3 class="fw-bold">50,000+</h3>
                    <p class="mb-0">Entregas Realizadas</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="d-flex flex-column align-items-center">
                    <i class="bi bi-star-fill fs-1 mb-3 text-warning"></i>
                    <h3 class="fw-bold">4.9/5</h3>
                    <p class="mb-0">Calificación Promedio</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h3 class="fw-bold mb-3">Mantente al día con nuestras ofertas</h3>
                <p class="text-muted mb-4">
                    Suscríbete a nuestro boletín y recibe descuentos exclusivos y novedades.
                </p>

                <form class="row g-3 justify-content-center">
                    <div class="col-md-6">
                        <input type="email" class="form-control form-control-lg"
                            placeholder="Tu correo electrónico" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-envelope me-2"></i>Suscribirse
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
    .hover-lift {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
    }

    .product-card {
        transition: all 0.3s ease;
    }

    .product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
    }

    .min-vh-50 {
        min-height: 50vh;
    }

    .bg-gradient-to-r {
        background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
    }
</style>
@endpush