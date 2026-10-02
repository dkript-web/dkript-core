<!-- Popover flotante de vista ampliada en hover (Desktop) -->
<div id="catalogHoverPreview" 
     class="fixed z-50 pointer-events-none opacity-0 scale-95 transition-all duration-200 p-2.5 rounded-2xl bg-[#071026]/95 border border-[#0062f5]/40 shadow-2xl backdrop-blur-md flex flex-col items-center justify-center text-center hidden md:flex"
     style="width: 140px; height: 140px;">
    <div class="w-full h-24 flex items-center justify-center p-1">
        <img id="hoverPreviewImg" src="" alt="Preview" class="max-h-full max-w-full object-contain filter drop-shadow-md transition-all">
    </div>
    <span id="hoverPreviewTitle" class="text-[10px] font-bold text-[#00d4ff] truncate w-full px-1 mt-1 block"></span>
</div>
