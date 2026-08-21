import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { motion } from "framer-motion";
import { useEffect } from "react";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { useMutation, useQuery } from "@tanstack/react-query";
import { apiRequest, queryClient } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import { Department, Unit, User, insertDepartmentSchema } from "@shared/schema";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import {
  Loader2,
  Building2,
  Users,
  FileText,
  CheckCircle2,
  Target,
  Briefcase,
  MapPin
} from "lucide-react";
import { cn } from "@/lib/utils";

interface DepartmentFormProps {
  department?: Department;
  onSuccess: () => void;
}

export function DepartmentForm({ department, onSuccess }: DepartmentFormProps) {
  const { toast } = useToast();
  const isEditing = !!department;

  // Create form schema
  const formSchema = insertDepartmentSchema;

  type FormValues = z.infer<typeof formSchema>;

  // Fetch units for selection
  const { data: units = [] } = useQuery<Unit[]>({
    queryKey: ["/api/masters/units"],
  });

  const { data: departments = [] } = useQuery<Department[]>({
    queryKey: ["/api/departments"],
  });

  const { data: employees = [] } = useQuery<User[]>({
    queryKey: ["/api/employees"],
  });

  // Set up form with default values
  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      name: department?.name || "",
      description: department?.description || "",
      code: department?.code || "",
      manager: department?.manager || "",
      location: department?.location || "",
      unitId: department?.unitId || undefined,
    },
  });

  // Set default unitId when units load
  useEffect(() => {
    if (units.length > 0 && !form.getValues("unitId") && !isEditing) {
      form.setValue("unitId", units[0].id);
    }
  }, [units, form, isEditing]);

  // Auto-generate department code based on selected unit
  const watchUnitId = form.watch("unitId");
  
  useEffect(() => {
    if (!isEditing && watchUnitId && units.length > 0) {
      const selectedUnit = units.find(u => u.id === watchUnitId);
      if (selectedUnit) {
        const unitCode = selectedUnit.code || selectedUnit.name.substring(0, 2).toUpperCase();
        
        // Find existing departments for this unit
        const unitDepts = departments.filter(d => d.unitId === watchUnitId);
        
        // Extract numbers from existing codes (e.g., "CT-01" -> 1)
        let maxNum = 0;
        unitDepts.forEach(d => {
          if (d.code && d.code.startsWith(`${unitCode}-`)) {
            const numPart = d.code.split('-')[1];
            if (numPart) {
              const num = parseInt(numPart, 10);
              if (!isNaN(num) && num > maxNum) {
                maxNum = num;
              }
            }
          }
        });
        
        // Generate new code
        const newCode = `${unitCode}-${String(maxNum + 1).padStart(2, '0')}`;
        
        if (form.getValues("code") !== newCode) {
          form.setValue("code", newCode, { shouldValidate: true, shouldDirty: true });
        }
      }
    }
  }, [watchUnitId, units, departments, isEditing, form]);

  // Create or update department mutation
  const mutation = useMutation({
    mutationFn: async (values: FormValues) => {
      if (isEditing) {
        return await apiRequest(
          "PUT",
          `/api/departments/${department.id}`,
          values
        );
      } else {
        return await apiRequest("POST", "/api/departments", values);
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/api/departments"] });
      toast({
        title: isEditing ? "Department updated" : "Department created",
        description: isEditing
          ? "Department information has been updated successfully."
          : "New department has been created successfully.",
      });
      onSuccess();
    },
    onError: (error: Error) => {
      toast({
        title: "Error",
        description: error.message,
        variant: "destructive",
      });
    },
  });

  // Form submission handler
  const onSubmit = (values: FormValues) => {
    mutation.mutate(values);
  };

  return (
    <div className="form-container pb-10">
      {/* Executive Header */}
      <div className="bg-gradient-to-r from-slate-50 via-slate-50 to-white -mx-6 -mt-6 px-6 pt-6 pb-6 mb-6 border-b-2 border-slate-200 shadow-sm">
        <div className="flex items-center justify-between mb-4">
          <div>
            <h2 className="text-2xl font-bold bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent mb-2">
              {isEditing ? "Update Department" : "Create New Department"}
            </h2>
            <p className="text-slate-600 text-sm">
              {isEditing
                ? "Modify department information and organizational structure"
                : "Set up a new department to organize your workforce"}
            </p>
          </div>
          <div className="hidden sm:block">
            <div className="bg-gradient-to-br from-teal-100 to-teal-200 p-4 rounded-xl shadow-sm">
              <Building2 className="w-8 h-8 text-teal-700" />
            </div>
          </div>
        </div>
      </div>

      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
            className="bg-white rounded-2xl border-2 border-slate-200 shadow-lg hover:shadow-xl transition-shadow duration-300"
          >
            <div className="bg-gradient-to-r from-slate-50 via-white to-slate-50 px-6 py-5 rounded-t-2xl border-b-2 border-slate-100">
              <h3 className="text-xl font-bold text-slate-900 flex items-center">
                <div className="bg-gradient-to-br from-teal-100 to-teal-200 p-3 rounded-xl mr-4 shadow-sm">
                  <Briefcase className="w-5 h-5 text-teal-700" />
                </div>
                Department Information
              </h3>
              <p className="text-sm text-slate-600 mt-2 ml-12 font-medium">Basic details and organizational structure</p>
            </div>

            <div className="p-6 space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Department Name Field */}
                <FormField
                  control={form.control}
                  name="name"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-sm font-bold text-slate-700 mb-2 block flex items-center">
                        <Building2 className="w-4 h-4 mr-2 text-teal-600" />
                        Department Name *
                      </FormLabel>
                      <FormControl>
                        <Input
                          placeholder="e.g., Human Resources"
                          className="h-12 border-2 border-slate-200 focus:border-teal-500 focus:ring-2 focus:ring-teal-100 rounded-lg text-sm font-medium transition-all duration-200 pl-4"
                          {...field}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Department Code Field */}
                <FormField
                  control={form.control}
                  name="code"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-sm font-bold text-slate-700 mb-2 block flex items-center">
                        <FileText className="w-4 h-4 mr-2 text-teal-600" />
                        Department Code *
                      </FormLabel>
                      <FormControl>
                        <Input
                          placeholder="e.g., HR-01"
                          className="h-12 border-2 border-slate-200 focus:border-teal-500 focus:ring-2 focus:ring-teal-100 rounded-lg text-sm font-medium transition-all duration-200 pl-4"
                          {...field}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Unit Selection Field */}
                <FormField
                  control={form.control}
                  name="unitId"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-sm font-bold text-slate-700 mb-2 block flex items-center">
                        <Building2 className="w-4 h-4 mr-2 text-teal-600" />
                        Unit *
                      </FormLabel>
                      <Select
                        onValueChange={(val) => field.onChange(parseInt(val, 10))}
                        value={field.value ? String(field.value) : ""}
                      >
                        <FormControl>
                          <SelectTrigger className="h-12 border-2 border-slate-200 focus:border-teal-500 rounded-lg text-sm font-medium">
                            <SelectValue placeholder="Select a Unit" />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          {units.map((u) => (
                            <SelectItem key={u.id} value={String(u.id)}>
                              {u.name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Manager Field */}
                <FormField
                  control={form.control}
                  name="manager"
                  render={({ field }) => {
                    const selectedUnitId = form.watch('unitId');
                    const availableManagers = employees.filter(emp => {
                      if (emp.role === 'admin') return true;
                      
                      if (!selectedUnitId) return false;
                      const dept = departments.find(d => d.id === emp.departmentId);
                      const isInUnit = dept && dept.unitId === selectedUnitId;
                      const isManagerRole = emp.role === 'hr';
                      return isInUnit && isManagerRole;
                    });
                    
                    return (
                      <FormItem>
                        <FormLabel className="text-sm font-bold text-slate-700 mb-2 block flex items-center">
                          <Users className="w-4 h-4 mr-2 text-teal-600" />
                          Manager
                        </FormLabel>
                        <Select
                          onValueChange={field.onChange}
                          value={field.value ?? ""}
                        >
                          <FormControl>
                            <SelectTrigger className="h-12 border-2 border-slate-200 focus:border-teal-500 rounded-lg text-sm font-medium">
                              <SelectValue placeholder="Select a Manager" />
                            </SelectTrigger>
                          </FormControl>
                          <SelectContent>
                            {availableManagers.length === 0 && (
                              <SelectItem value="none" disabled>
                                No employees in this company
                              </SelectItem>
                            )}
                            {availableManagers.map((m) => (
                              <SelectItem key={m.id} value={`${m.firstName} ${m.lastName}`}>
                                {m.firstName} {m.lastName} {m.role === 'manager' ? '(Manager)' : ''}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                        <FormMessage />
                      </FormItem>
                    );
                  }}
                />

                {/* Location Field */}
                <FormField
                  control={form.control}
                  name="location"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-sm font-bold text-slate-700 mb-2 block flex items-center">
                        <MapPin className="w-4 h-4 mr-2 text-teal-600" />
                        Location
                      </FormLabel>
                      <FormControl>
                        <Input
                          placeholder="Enter location"
                          className="h-12 border-2 border-slate-200 focus:border-teal-500 focus:ring-2 focus:ring-teal-100 rounded-lg text-sm font-medium transition-all duration-200 pl-4"
                          {...field}
                          value={field.value ?? ""}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>

              {/* Description Field */}
              <FormField
                control={form.control}
                name="description"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-sm font-bold text-slate-700 mb-2 block flex items-center">
                      <FileText className="w-4 h-4 mr-2 text-teal-600" />
                      Department Description
                    </FormLabel>
                    <FormControl>
                      <Textarea
                        placeholder="Describe the department's role, responsibilities, and objectives within the organization..."
                        value={field.value || ""}
                        onChange={field.onChange}
                        onBlur={field.onBlur}
                        name={field.name}
                        className="resize-none border-2 border-slate-200 focus:border-teal-500 focus:ring-2 focus:ring-teal-100 rounded-lg text-sm font-medium transition-all duration-200 min-h-[120px]"
                        rows={5}
                      />
                    </FormControl>
                    <FormMessage />
                    <div className="text-xs text-slate-500 mt-2 flex items-center">
                      <Target className="w-3 h-3 mr-1" />
                      Include department goals, key functions, and team structure
                    </div>
                  </FormItem>
                )}
              />

              {/* Department Features Info */}
              <div className="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-5 border border-blue-200">
                <div className="flex items-start space-x-3">
                  <div className="bg-blue-500 p-2 rounded-lg">
                    <Users className="w-5 h-5 text-white" />
                  </div>
                  <div className="flex-1">
                    <h4 className="text-sm font-bold text-blue-900 mb-2">Department Benefits</h4>
                    <div className="space-y-2 text-sm text-blue-800">
                      <div className="flex items-center space-x-2">
                        <CheckCircle2 className="w-4 h-4 text-blue-600" />
                        <span>Organize employees into logical groups</span>
                      </div>
                      <div className="flex items-center space-x-2">
                        <CheckCircle2 className="w-4 h-4 text-blue-600" />
                        <span>Streamline reporting and management structure</span>
                      </div>
                      <div className="flex items-center space-x-2">
                        <CheckCircle2 className="w-4 h-4 text-blue-600" />
                        <span>Enable better resource allocation and planning</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </motion.div>

          {/* Form Navigation */}
          <div className="bg-white border-2 border-slate-200 shadow-lg px-6 py-4 rounded-lg">
            <div className="flex flex-col-reverse sm:flex-row sm:justify-between sm:space-x-4 space-y-3 space-y-reverse sm:space-y-0">
              <div className="flex space-x-3">
                <Button
                  type="button"
                  variant="outline"
                  onClick={onSuccess}
                  className="w-full sm:w-auto h-11 px-6 border-2 border-slate-300 hover:border-slate-400 font-medium transition-all duration-200"
                >
                  Cancel
                </Button>
              </div>

              <div className="flex space-x-3">
                <Button
                  type="submit"
                  className={cn(
                    "w-full sm:w-auto h-11 px-8 font-semibold transition-all duration-200 shadow-lg hover:shadow-xl",
                    "bg-gradient-to-r from-teal-600 via-teal-600 to-emerald-600 hover:from-teal-700 hover:via-teal-700 hover:to-emerald-700"
                  )}
                  disabled={mutation.isPending}
                >
                  {mutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                  {!mutation.isPending && (
                    <Building2 className="mr-2 h-4 w-4" />
                  )}
                  {isEditing ? "Update Department" : "Create Department"}
                </Button>
              </div>
            </div>
          </div>
        </form>
      </Form>
    </div>
  );
}