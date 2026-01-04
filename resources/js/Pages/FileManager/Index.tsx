import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useState, useEffect, useCallback } from 'react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { 
    FolderIcon, 
    DocumentIcon, 
    ArrowLeftIcon, 
    PlusIcon, 
    TrashIcon, 
    PencilIcon,
    PencilSquareIcon,
    ArrowUpTrayIcon,
    ArrowDownTrayIcon,
    ArrowsRightLeftIcon,
    MagnifyingGlassIcon,
    XMarkIcon
} from '@heroicons/react/24/outline';

interface FileItem {
    name: string;
    type: 'file' | 'directory';
    size: number;
    modified: number;
    permissions: string;
}

export default function Index({ initialPath }: { initialPath: string }) {
    const [path, setPath] = useState(initialPath);
    const [files, setFiles] = useState<FileItem[]>([]);
    const [loading, setLoading] = useState(true);
    const [editingFile, setEditingFile] = useState<{name: string, content: string} | null>(null);
    const [newFolderName, setNewFolderName] = useState('');
    const [isCreatingFolder, setIsCreatingFolder] = useState(false);
    const [renamingItem, setRenamingItem] = useState<{oldName: string, newName: string} | null>(null);
    const [movingItem, setMovingItem] = useState<{name: string, destination: string} | null>(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [isDragging, setIsDragging] = useState(false);

    const fetchFiles = useCallback(async (currentPath: string) => {
        setLoading(true);
        try {
            const response = await fetch(`/file-manager/list?path=${encodeURIComponent(currentPath)}`);
            const data = await response.json();
            if (Array.isArray(data)) {
                setFiles(data);
            } else {
                console.error('Failed to fetch files:', data.error);
            }
        } catch (error) {
            console.error('Error fetching files:', error);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchFiles(path);
    }, [path, fetchFiles]);

    const navigateTo = (name: string) => {
        const newPath = path === '/' ? `/${name}` : `${path}/${name}`;
        setPath(newPath);
    };

    const goBack = () => {
        if (path === initialPath) return;
        const parts = path.split('/').filter(Boolean);
        parts.pop();
        const newPath = '/' + parts.join('/');
        if (newPath.length < initialPath.length && !newPath.startsWith(initialPath)) {
            setPath(initialPath);
        } else {
            setPath(newPath);
        }
    };

    const deleteItem = async (name: string) => {
        if (!confirm(`Are you sure you want to delete ${name}?`)) return;
        
        const itemPath = path.endsWith('/') ? `${path}${name}` : `${path}/${name}`;
        try {
            const response = await fetch('/file-manager/delete', {
                method: 'DELETE',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content 
                },
                body: JSON.stringify({ path: itemPath })
            });
            if (response.ok) {
                fetchFiles(path);
            }
        } catch (error) {
            console.error('Error deleting item:', error);
        }
    };

    const createFolder = async () => {
        if (!newFolderName) return;
        const folderPath = path.endsWith('/') ? `${path}${newFolderName}` : `${path}/${newFolderName}`;
        try {
            const response = await fetch('/file-manager/create-directory', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content 
                },
                body: JSON.stringify({ path: folderPath })
            });
            if (response.ok) {
                setNewFolderName('');
                setIsCreatingFolder(false);
                fetchFiles(path);
            }
        } catch (error) {
            console.error('Error creating folder:', error);
        }
    };

    const renameItem = async () => {
        if (!renamingItem || !renamingItem.newName) return;
        const fromPath = path.endsWith('/') ? `${path}${renamingItem.oldName}` : `${path}/${renamingItem.oldName}`;
        const toPath = path.endsWith('/') ? `${path}${renamingItem.newName}` : `${path}/${renamingItem.newName}`;
        
        try {
            const response = await fetch('/file-manager/rename', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content 
                },
                body: JSON.stringify({ from: fromPath, to: toPath })
            });
            if (response.ok) {
                setRenamingItem(null);
                fetchFiles(path);
            }
        } catch (error) {
            console.error('Error renaming item:', error);
        }
    };

    const moveItem = async () => {
        if (!movingItem || !movingItem.destination) return;
        const fromPath = path.endsWith('/') ? `${path}${movingItem.name}` : `${path}/${movingItem.name}`;
        let toPath = movingItem.destination;
        
        if (!toPath.startsWith('/')) {
            toPath = path.endsWith('/') ? `${path}${toPath}` : `${path}/${toPath}`;
        }

        if (toPath.endsWith('/')) {
            toPath += movingItem.name;
        }
        
        try {
            const response = await fetch('/file-manager/rename', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content 
                },
                body: JSON.stringify({ from: fromPath, to: toPath })
            });
            if (response.ok) {
                setMovingItem(null);
                fetchFiles(path);
            }
        } catch (error) {
            console.error('Error moving item:', error);
        }
    };

    const editFile = async (name: string) => {
        const filePath = path.endsWith('/') ? `${path}${name}` : `${path}/${name}`;
        try {
            const response = await fetch(`/file-manager/read?path=${encodeURIComponent(filePath)}`);
            const data = await response.json();
            setEditingFile({ name, content: data.content });
        } catch (error) {
            console.error('Error reading file:', error);
        }
    };

    const saveFile = async () => {
        if (!editingFile) return;
        const filePath = path.endsWith('/') ? `${path}${editingFile.name}` : `${path}/${editingFile.name}`;
        try {
            const response = await fetch('/file-manager/write', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content 
                },
                body: JSON.stringify({ path: filePath, content: editingFile.content })
            });
            if (response.ok) {
                setEditingFile(null);
                fetchFiles(path);
            }
        } catch (error) {
            console.error('Error saving file:', error);
        }
    };

    const handleUpload = async (filesToUpload: FileList | null) => {
        if (!filesToUpload || filesToUpload.length === 0) return;

        for (let i = 0; i < filesToUpload.length; i++) {
            const file = filesToUpload[i];
            const formData = new FormData();
            formData.append('file', file);
            formData.append('path', path);
            formData.append('_token', (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '');

            try {
                await fetch('/file-manager/upload', {
                    method: 'POST',
                    body: formData
                });
            } catch (error) {
                console.error('Error uploading file:', error);
            }
        }
        fetchFiles(path);
    };

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(true);
    };

    const handleDragLeave = () => {
        setIsDragging(false);
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
        handleUpload(e.dataTransfer.files);
    };

    const filteredFiles = files.filter(file => 
        file.name.toLowerCase().includes(searchQuery.toLowerCase())
    );

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        File Manager
                    </h2>
                    <div className="flex gap-2">
                        <label className="cursor-pointer">
                            <PrimaryButton className="flex items-center gap-2">
                                <ArrowUpTrayIcon className="w-5 h-5" />
                                Upload
                            </PrimaryButton>
                            <input type="file" className="hidden" multiple onChange={(e) => handleUpload(e.target.files)} />
                        </label>
                        <PrimaryButton 
                            onClick={() => setIsCreatingFolder(true)}
                            className="flex items-center gap-2 bg-green-600 hover:bg-green-700"
                        >
                            <PlusIcon className="w-5 h-5" />
                            New Folder
                        </PrimaryButton>
                    </div>
                </div>
            }
            breadcrumbs={[{ title: 'File Manager' }]}
        >
            <Head title="File Manager" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div 
                        className={`overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800 transition-colors ${isDragging ? 'ring-4 ring-indigo-500 ring-inset' : ''}`}
                        onDragOver={handleDragOver}
                        onDragLeave={handleDragLeave}
                        onDrop={handleDrop}
                    >
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            
                            {/* Toolbar */}
                            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                                <div className="flex items-center gap-4 bg-gray-100 dark:bg-gray-700 p-2 rounded-lg flex-1">
                                    <button 
                                        onClick={goBack}
                                        disabled={path === initialPath}
                                        className="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-full disabled:opacity-50"
                                    >
                                        <ArrowLeftIcon className="w-5 h-5" />
                                    </button>
                                    <div className="flex items-center gap-1 font-mono text-sm overflow-x-auto whitespace-nowrap">
                                        <button 
                                            onClick={() => setPath(initialPath)}
                                            className={`hover:text-indigo-600 dark:hover:text-indigo-400 ${path === initialPath ? 'font-bold' : ''}`}
                                        >
                                            home
                                        </button>
                                        {path.replace(initialPath, '').split('/').filter(Boolean).map((part, i, parts) => (
                                            <div key={i} className="flex items-center gap-1">
                                                <span className="text-gray-400">/</span>
                                                <button 
                                                    onClick={() => {
                                                        const subPath = parts.slice(0, i + 1).join('/');
                                                        setPath(initialPath.endsWith('/') ? `${initialPath}${subPath}` : `${initialPath}/${subPath}`);
                                                    }}
                                                    className={`hover:text-indigo-600 dark:hover:text-indigo-400 ${i === parts.length - 1 ? 'font-bold' : ''}`}
                                                >
                                                    {part}
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                <div className="relative w-full md:w-64">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                                    </div>
                                    <TextInput
                                        type="text"
                                        className="block w-full pl-10 pr-3 py-2"
                                        placeholder="Search files..."
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                    />
                                    {searchQuery && (
                                        <button 
                                            onClick={() => setSearchQuery('')}
                                            className="absolute inset-y-0 right-0 pr-3 flex items-center"
                                        >
                                            <XMarkIcon className="h-5 w-5 text-gray-400 hover:text-gray-600" />
                                        </button>
                                    )}
                                </div>
                            </div>

                            {isCreatingFolder && (
                                <div className="mb-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg flex gap-4 items-center bg-gray-50 dark:bg-gray-900/50">
                                    <TextInput 
                                        type="text" 
                                        value={newFolderName}
                                        onChange={(e) => setNewFolderName(e.target.value)}
                                        placeholder="Folder name"
                                        className="flex-1"
                                        autoFocus
                                        onKeyDown={(e) => e.key === 'Enter' && createFolder()}
                                    />
                                    <PrimaryButton onClick={createFolder}>Create</PrimaryButton>
                                    <SecondaryButton onClick={() => setIsCreatingFolder(false)}>Cancel</SecondaryButton>
                                </div>
                            )}

                            {renamingItem && (
                                <div className="mb-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg flex gap-4 items-center bg-gray-50 dark:bg-gray-900/50">
                                    <div className="text-sm text-gray-500">Rename <span className="font-bold">{renamingItem.oldName}</span> to:</div>
                                    <TextInput 
                                        type="text" 
                                        value={renamingItem.newName}
                                        onChange={(e) => setRenamingItem({...renamingItem, newName: e.target.value})}
                                        className="flex-1"
                                        autoFocus
                                        onKeyDown={(e) => e.key === 'Enter' && renameItem()}
                                    />
                                    <PrimaryButton onClick={renameItem}>Rename</PrimaryButton>
                                    <SecondaryButton onClick={() => setRenamingItem(null)}>Cancel</SecondaryButton>
                                </div>
                            )}

                            {movingItem && (
                                <div className="mb-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg flex gap-4 items-center bg-gray-50 dark:bg-gray-900/50">
                                    <div className="text-sm text-gray-500">Move <span className="font-bold">{movingItem.name}</span> to:</div>
                                    <TextInput 
                                        type="text" 
                                        value={movingItem.destination}
                                        onChange={(e) => setMovingItem({...movingItem, destination: e.target.value})}
                                        placeholder="Destination path (e.g. /other-folder/ or new-name)"
                                        className="flex-1"
                                        autoFocus
                                        onKeyDown={(e) => e.key === 'Enter' && moveItem()}
                                    />
                                    <PrimaryButton onClick={moveItem}>Move</PrimaryButton>
                                    <SecondaryButton onClick={() => setMovingItem(null)}>Cancel</SecondaryButton>
                                </div>
                            )}

                            {editingFile ? (
                                <div className="space-y-4">
                                    <div className="flex justify-between items-center">
                                        <h3 className="text-lg font-medium">Editing: {editingFile.name}</h3>
                                        <div className="flex gap-2">
                                            <PrimaryButton onClick={saveFile} className="bg-green-600 hover:bg-green-700">
                                                Save Changes
                                            </PrimaryButton>
                                            <SecondaryButton onClick={() => setEditingFile(null)}>
                                                Cancel
                                            </SecondaryButton>
                                        </div>
                                    </div>
                                    <textarea 
                                        value={editingFile.content}
                                        onChange={(e) => setEditingFile({...editingFile, content: e.target.value})}
                                        className="w-full h-[600px] font-mono text-sm p-4 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                        spellCheck={false}
                                    />
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left border-collapse">
                                        <thead>
                                            <tr className="border-b border-gray-200 dark:border-gray-700">
                                                <th className="py-3 px-4 font-semibold">Name</th>
                                                <th className="py-3 px-4 font-semibold">Size</th>
                                                <th className="py-3 px-4 font-semibold">Permissions</th>
                                                <th className="py-3 px-4 font-semibold">Modified</th>
                                                <th className="py-3 px-4 font-semibold text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {loading ? (
                                                <tr>
                                                    <td colSpan={5} className="py-8 text-center text-gray-500">
                                                        <div className="flex flex-col items-center gap-2">
                                                            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
                                                            <span>Loading files...</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ) : filteredFiles.length === 0 ? (
                                                <tr>
                                                    <td colSpan={5} className="py-8 text-center text-gray-500">
                                                        {searchQuery ? `No files matching "${searchQuery}"` : 'This directory is empty.'}
                                                    </td>
                                                </tr>
                                            ) : (
                                                filteredFiles.map((file) => (
                                                    <tr key={file.name} className="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 group">
                                                        <td className="py-3 px-4">
                                                            <div className="flex items-center gap-3">
                                                                {file.type === 'directory' ? (
                                                                    <FolderIcon className="w-5 h-5 text-yellow-500" />
                                                                ) : (
                                                                    <DocumentIcon className="w-5 h-5 text-blue-500" />
                                                                )}
                                                                <button 
                                                                    onClick={() => file.type === 'directory' ? navigateTo(file.name) : editFile(file.name)}
                                                                    className="hover:underline text-indigo-600 dark:text-indigo-400 font-medium"
                                                                >
                                                                    {file.name}
                                                                </button>
                                                            </div>
                                                        </td>
                                                        <td className="py-3 px-4 text-sm text-gray-500">
                                                            {file.type === 'file' ? `${(file.size / 1024).toFixed(2)} KB` : '-'}
                                                        </td>
                                                        <td className="py-3 px-4 text-sm font-mono text-gray-500">
                                                            {file.permissions}
                                                        </td>
                                                        <td className="py-3 px-4 text-sm text-gray-500">
                                                            {new Date(file.modified * 1000).toLocaleString()}
                                                        </td>
                                                        <td className="py-3 px-4 text-right">
                                                            <div className="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                                <button 
                                                                    onClick={() => setRenamingItem({oldName: file.name, newName: file.name})}
                                                                    className="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md text-gray-500 hover:text-indigo-600"
                                                                    title="Rename"
                                                                >
                                                                    <PencilSquareIcon className="w-5 h-5" />
                                                                </button>
                                                                {file.type === 'file' && (
                                                                    <button 
                                                                        onClick={() => {
                                                                            const filePath = path.endsWith('/') ? `${path}${file.name}` : `${path}/${file.name}`;
                                                                            window.location.href = `/file-manager/download?path=${encodeURIComponent(filePath)}`;
                                                                        }}
                                                                        className="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md text-gray-500 hover:text-indigo-600"
                                                                        title="Download"
                                                                    >
                                                                        <ArrowDownTrayIcon className="w-5 h-5" />
                                                                    </button>
                                                                )}
                                                                <button 
                                                                    onClick={() => setMovingItem({name: file.name, destination: path.endsWith('/') ? path : path + '/'})}
                                                                    className="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md text-gray-500 hover:text-indigo-600"
                                                                    title="Move"
                                                                >
                                                                    <ArrowsRightLeftIcon className="w-5 h-5" />
                                                                </button>
                                                                {file.type === 'file' && (
                                                                    <button 
                                                                        onClick={() => editFile(file.name)}
                                                                        className="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md text-gray-500 hover:text-indigo-600"
                                                                        title="Edit"
                                                                    >
                                                                        <PencilIcon className="w-5 h-5" />
                                                                    </button>
                                                                )}
                                                                <button 
                                                                    onClick={() => deleteItem(file.name)}
                                                                    className="p-2 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-md text-gray-500 hover:text-red-600"
                                                                    title="Delete"
                                                                >
                                                                    <TrashIcon className="w-5 h-5" />
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
