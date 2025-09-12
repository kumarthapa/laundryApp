@php
    $shotcuts_modules = [];
    $shotcuts_modules = App\Helpers\UtilityHelper::shortcutsNavigationModules();
@endphp
<div class="modal fade" id="easy_nav_nodal" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalCenterTitle">
                    <span><i class='bx bxs-right-arrow-circle bx-flip-vertical fs-3'></i></span>
                    Shortcuts Navigation
                    {{-- <a href="#" class="py-2"><i
                    class="bx bx-plus-circle text-heading text-primary fs-3"></i></a> --}}
                </h4>
                <button type="button" class="btn btn-label-danger p-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">

                    @if ($shotcuts_modules && count($shotcuts_modules) > 0)
                        @foreach ($shotcuts_modules as $data)
                            <div class="col-md-6 col-12 p-3">
                                <div class="custom-option custom-option-icon">
                                    <div class="d-flex flex-column align-items-center p-3">
                                        <a href="{{ $data["url"] ?? "" }}" title="{{ $data["title"] ?? "" }}"
                                            class="text-center">
                                            <span class="mb-2">
                                                <i
                                                    class="{{ $data["icon"] ?? "" }} fs-3 text-primary p-3 bg-label-primary rounded-circle mb-2"></i>
                                            </span>
                                            <p class="card-title">{{ $data["title"] ?? "" }}</p>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="col-md-12 col-12 p-3">
                            <div class="custom-option custom-option-icon">
                                <div class="d-flex flex-column align-items-center p-3">
                                    <a href="#" class="text-center">
                                        <span class="mb-2">
                                            <i class="bx bx-info-circle fs-1 text-danger mb-2"></i>
                                        </span>
                                        <div class="alert alert-warning" role="alert">
                                            <h5 class="alert-heading mb-1">Permission denied!</h5>
                                            <span>You have no permission to use Shortcuts</span>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                {{-- <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button> --}}
            </div>
        </div>
    </div>
</div>
